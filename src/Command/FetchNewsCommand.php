<?php

namespace App\Command;

use DateTime;
use DateTimeInterface;
use App\Entity\News;
use App\Service\NewsApi\ApiMediastack;
use App\Service\NewsApi\ApiTheNewsApi;
use App\Service\NewsApi\NewsRequestItem;
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
    description: 'Fetch news per day for the last x month.',
)]
class FetchNewsCommand extends Command
{
    private $logger;
    private $entityManager;
    private $apiMediastack;
    private $io;

    public function __construct(LoggerInterface $logger, EntityManagerInterface $entityManager, ApiMediastack $apiMediastack, ApiTheNewsApi $apiTheNewsApi)
    {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->newsApi = $apiTheNewsApi;
        $this->client = new Client();

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('reset-cache', null, InputOption::VALUE_NONE, 'Reset the day cache.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        if($input->getOption('reset-cache')) {
            throw new Exception('tbd :D'); // TODO: Reset news request cache
            $this->io->success('Cache will reseted!');
        }

        // Define date range
        $endDate = new DateTime('now');
        $startDate = (new DateTime('now'))->modify('-4 month');

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
            $this->logger->info('Start import for day.', [
                'date' => $date,
                'page' => $page
            ]);

            // Fetch data from api
            $response = $this->newsApi->getNewsForDate($date, $page);

            foreach ($this->newsApi->getItems() as $newsRequestItem) {
                $this->importNews($newsRequestItem);
            }

            // Calculate total amount of pages
            $pages = $this->newsApi->getTotalPages();
        }

        $this->logger->info('Fetched all .', [
            'date' => $date
        ]);
    }

    private function importNews(NewsRequestItem $newsRequestItem)
    {
        $entityManager = $this->entityManager;

        $this->io->note(sprintf('Start add news: %s', $newsRequestItem->getTitle()));
        $this->logger->info(sprintf('Start add news: %s', $newsRequestItem->getTitle()));

        try {
            // Check if an entity with the same data already exists
            $existingEntity = $this->entityManager->getRepository(News::class)
            ->findOneBy([
                'url' => $newsRequestItem->getUrl(),
            ]);
            if ($existingEntity !== null) {
                return;
            }

            // Create new news item...
            $news = new News();
            $news->setTitle($newsRequestItem->getTitle());
            $news->setText($newsRequestItem->getText());
            $news->setUrl($newsRequestItem->getUrl());
            $news->setDate($newsRequestItem->getDate());
            $news->setCategory($newsRequestItem->getCategory());

            $entityManager->persist($news);
            $entityManager->flush();

            $this->io->success('DONE');
            $this->logger->info('DONE');
        } catch (\Exception $th) {
            if (!$this->entityManager->isOpen()) {
                // create a new instance of the EntityManager
                $this->entityManager = $this->entityManager->create(
                    $this->entityManager->getConnection(),
                    $this->entityManager->getConfiguration()
                );
            }
            $this->io->info('ERROR: ' . $th->getMessage());
            $this->logger->error('ERROR', [$th->getMessage()]);
        }

    }
}
