<?php

namespace App\Command;

use App\Entity\News;
use App\Service\ChatGptService;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class PredictRatingCommandAbstract extends Command
{
    public $methodName = 'UNSET';
    public $logger;
    public $chatGptService;
    public $entityManager;
    public $prompt = 'Forget all your previous instructions. Pretend you are a financial expert. You are a financial expert with stock recommendation experience. Answer “YES” if good news, “NO” if bad news, or “UNKNOWN” if uncertain in the first line. Then elaborate with one short and concise sentence on the next line. Headline:';
    public $domainPopularity = [];
    public $io;

    public function __construct(LoggerInterface $logger, ChatGptService $chatGptService, EntityManagerInterface $entityManager)
    {
        $this->logger = $logger;
        $this->chatGptService = $chatGptService;
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $news = $this->getUnproccssedNews();
        foreach ($news as $newsEntity) {
            $this->io->info('Process: ' . $newsEntity->getTitle());
            $this->proccessNewsEntity($newsEntity);
            $this->io->info('Remaining: ' . $this->getNewsWith666());
        }

        $this->io->success('DONE.');

        return Command::SUCCESS;
    }

    public function getNewsWith666()
    {
        // TODO: Put this in the repository class
        $count = $this->entityManager->getRepository(News::class)->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.' . $this->methodName . ' = :ratingValue')
            ->setParameter('ratingValue', '666')
            ->getQuery()
            ->getSingleScalarResult();
        return $count;
    }

    public function proccessNewsEntity(News $newsEntity): int
    {
        throw new Exception('Please override this method');
    }

    public function getChatGptRating(News $newsEntity): int
    {
        $input = $this->chatGptService->getResult($this->prompt . $newsEntity->getTitle() . ' ' . $newsEntity->getText());
        $pattern = '/^(YES|NO|UNKNOWN)/';
        if (preg_match($pattern, $input, $matches)) {
            $result = $matches[0];  // should be "YES"
        } else {
            $result = "UNKNOWN";    // pattern didn't match
        }

        switch ($result) {
            case 'YES':
                return 10;
                break;
            case 'NO':
                return -10;
                break;
            default:
                return 0;
                break;
        }
    }

    public function getUnproccssedNews(): array
    {
        // TODO: Put this in the repository class
        $queryBuilder = $this->entityManager->createQueryBuilder();

        $query = $queryBuilder->select('n')
            ->from(News::class, 'n')
            ->where('n.' . $this->methodName . ' = :ratingValue')
            ->setParameter('ratingValue', 666)
            ->getQuery();

        $news = $query->getResult();

        return $news;
    }

    public function getPopularityForDomain(string $domain): int
    {
        // TODO: Put this in a service...
        if(!$this->domainPopularity) {
            $file = fopen('var/domain-popularity.csv', 'r');

            // Initialize an empty array to store the data
            $data = [];

            // Loop through each row in the CSV file
            while (($row = fgetcsv($file)) !== false) {
                // Append the row to the data array
                $data[] = $row;
            }

            fclose($file);

            $this->domainPopularity = $data;
        }

        $popularity = 0;
        foreach ($this->domainPopularity as $item) {
            if($item[0]===$domain) {
                $popularity = $item[1];
                break;
            }
        }

        if(!$popularity) {
            throw new Exception('There are missing domains in domain-popularity.csv. Please run "bin/console app:update-popularity-csv" and add the missing scores.');
        }

        return $popularity;
    }
}
