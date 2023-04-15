<?php

namespace App\Command;

use App\Entity\News;
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
        $io = new SymfonyStyle($input, $output);

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
                $outputArray[] = [$domain, 0];
            }
        }


        // Write csv.
        $fp = fopen($this->filePath, 'w');
        foreach ($outputArray as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);

        $io->info(sprintf('<info>CSV file written to: %s</info>', $this->filePath));

        return Command::SUCCESS;
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
