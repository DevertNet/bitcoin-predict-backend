<?php

namespace App\Command;

use DateTime;
use DateTimeInterface;
use App\Entity\News;
use App\Entity\Mediastack;
use GuzzleHttp\Client;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fetch-news',
    description: 'Fetch news from mediastack.',
)]
class FetchNewsCommand extends Command
{
    private $mediastackLimit = 10;
    private $logger;
    private $entityManager;
    private $io;

    public function __construct(LoggerInterface $logger, EntityManagerInterface $entityManager)
    {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->client = new Client();

        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        // Define date range
        $endDate = new DateTime('now');
        $startDate = (new DateTime('now'))->modify('-1 days');

        // Fetch news for each day
        $date = clone $endDate;
        while ($date >= $startDate) {
            $this->fetchNewsForDate($date);
            $date->modify('-1 day');
        }

        return Command::SUCCESS;
    }

    private function fetchNewsForDate(DateTime $date): void
    {
        // Will be calculated inside the for
        $pages = 1;

        // fetch news articles for each page
        for ($page = 1; $page <= $pages; $page++) {
            $mediastackItem = $this->getMediastackItemForDate($date, $page);

            // Check if we already fetched this day complete
            if($mediastackItem->getDone()) {
                $this->logger->info('Skip date, because its done.', [
                    'date' => $date
                ]);
                return;
            }

            $this->logger->info('Start import for day.', [
                'date' => $date,
                'page' => $page
            ]);

            // Fetch data from api
            $response = $this->getDataFromApi($date);
            $this->importNews($response['data']);

            // Calculate total amount of pages
            $pages = ceil($response['pagination']['total'] / $this->mediastackLimit);
        }

        $this->logger->info('Fetched all .', [
            'date' => $date
        ]);

        // Set mediastack date to done
        $mediastackItem->setDone(1);
        $this->entityManager->flush();
    }

    private function getMediastackItemForDate(DateTime $date): Mediastack
    {
        $mediastackItem = $this->entityManager->getRepository(Mediastack::class)->findOneBy([
            'date' => $date,
        ]);

        if (!$mediastackItem) {
            $mediastackItem = new Mediastack();
            $mediastackItem->setDate($date);
            $mediastackItem->setDone(0);
            $this->entityManager->persist($mediastackItem);
            $this->entityManager->flush();
        }

        return $mediastackItem;
    }

    private function importNews(array $mediastackNews)
    {
        $entityManager = $this->entityManager;
        foreach ($mediastackNews as $newsItem) {
            $this->io->note(sprintf('Start add news: %s', $newsItem['title']));
            $this->logger->info(sprintf('Start add news: %s', $newsItem['title']));

            try {
                $news = new News();
                $news->setTitle($newsItem['title']);
                $news->setText($newsItem['description']);
                $news->setUrl($newsItem['url']);
                $news->setDate(new \DateTime($newsItem['published_at']));
                //$news->setPredictRatingV1(0);
                $entityManager->persist($news);

                $this->io->success('DONE');
                $this->logger->info('DONE');
            } catch (\Exception $th) {
                $this->io->error('ERROR: ' . $th->getMessage());
                $this->logger->error('ERROR', $th->getMessage());
            }
        }
        $entityManager->flush();
    }

    protected function getDataFromApi(DateTime $date, int $page = 1): array
    {
        try {
            $response = $this->client->request('GET', 'http://api.mediastack.com/v1/news', [
                'query' => [
                    'access_key' => $_ENV['MEDIASTACK_API_KEY'],
                    'languages' => 'en',
                    'categories' => 'technology,business',
                    'keywords' => 'crypto bitcoin',
                    'date' => $date->format('Y-m-d'),
                    'offset' => ($page - 1) * $this->mediastackLimit,
                    'limit' => $this->mediastackLimit
                ]
            ]);
            $data = json_decode($response->getBody(), true);
            return $data;
        } catch (\Exception $th) {
            $this->logger->error('Cant fetch data from mediastack api: ', [$th->getMessage()]);
        }

        return [];
    }
}
