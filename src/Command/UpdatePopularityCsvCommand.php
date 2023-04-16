<?php

namespace App\Command;

use App\Entity\News;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-popularity-csv',
    description: 'Update the csv with domain popularity infos.',
)]
class UpdatePopularityCsvCommand extends Command
{
    private $filePath = 'var/domain-popularity.csv';
    private $entityManager;
    private $io;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;

        parent::__construct();
    }


    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $outputArray = $this->readCsv();

        // add missing domains.
        foreach ($this->getNewsDomainsWithoutWww() as $domain) {
            $found = false;
            foreach ($outputArray as $output) {
                if ($output[0] === $domain) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $outputArray[] = [$domain, $this->getGlobalRankFromSimlarWebForDomain($domain)];
            }
        }


        // Write csv.
        $fp = fopen($this->filePath, 'w');
        foreach ($outputArray as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);

        $this->io->info(sprintf('<info>CSV file written to: %s</info>', $this->filePath));

        return Command::SUCCESS;
    }

    public function getGlobalRankFromSimlarWebForDomain(string $domain): int
    {
        $client = new Client();

        try {
            $response = $client->request('GET', 'https://api.similarweb.com/v1/similar-rank/'.$domain.'/rank?api_key='.$_ENV['SIMLARWEB_API_KEY'], [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Encoding' => 'gzip, deflate, br',
                    'Accept-Language' => 'en-US,en;q=0.5',
                    'Referer' => 'https://www.google.com/',
                    'Upgrade-Insecure-Requests' => '1',
                    'Connection' => 'keep-alive'
                ]
            ]);
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                $this->io->info('Could not get global rank for domain: ' . $domain);
                return 0;
            } else {
                $this->io->error($e->getMessage());
            }
        }

        $data = json_decode($response->getBody()->getContents());

        return $data->similar_rank->rank;
    }

    public function getNewsDomainsWithoutWww(): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();

        $query = $queryBuilder->select('n')
            ->from(News::class, 'n')
            ->getQuery();

        $news = $query->getResult();

        $out = [];
        foreach ($news as $newsEntity) {
            $domain = preg_replace('/^www\./', '', parse_url($newsEntity->getUrl(), PHP_URL_HOST));

            if(!in_array($domain, $out)) {
                $out[] = $domain;
            }
        }

        return $out;
    }

    public function readCsv()
    {
        $file = fopen($this->filePath, 'r');

        // Initialize an empty array to store the data
        $data = [];

        // Loop through each row in the CSV file
        while (($row = fgetcsv($file)) !== false) {
            // Append the row to the data array
            $data[] = $row;
        }

        fclose($file);

        return $data;
    }
}
