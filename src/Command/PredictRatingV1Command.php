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

#[AsCommand(
    name: 'app:predict-rating-v1',
    description: 'Calc ratings for predictRatingV1 model',
)]
class PredictRatingV1Command extends Command
{
    private $logger;
    private $chatGptService;
    private $entityManager;
    private $prompt = 'Forget all your previous instructions. Pretend you are a financial expert. You are a financial expert with stock recommendation experience. Answer “YES” if good news, “NO” if bad news, or “UNKNOWN” if uncertain in the first line. Then elaborate with one short and concise sentence on the next line. Headline:';

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
        $io = new SymfonyStyle($input, $output);

        $news = $this->getUnproccssedNews();
        foreach ($news as $newsEntity) {
            $io->info('Process: ' . $newsEntity->getTitle());
            $this->proccessNewsEntity($newsEntity);
        }

        $io->success('DONE.');

        return Command::SUCCESS;
    }

    public function proccessNewsEntity(News $newsEntity): int
    {
        $rating = $this->getChatGptRating($newsEntity);

        $this->logger->info('Ratet V1', [
            'news' => $newsEntity->getTitle(),
            'rating' => $rating
        ]);
        $newsEntity->setPredictRatingV1($rating);

        // Save the changes to the database
        $this->entityManager->flush();

        return $rating;
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
                return 5;
                break;
            case 'NO':
                return -5;
                break;
            default:
                return 0;
                break;
        }
    }

    public function getUnproccssedNews(): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();

        $query = $queryBuilder->select('n')
            ->from(News::class, 'n')
            ->where('n.predictRatingV1 = :predictRatingV1')
            ->setParameter('predictRatingV1', 666)
            ->getQuery();

        $news = $query->getResult();

        return $news;
    }
}
