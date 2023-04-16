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
    private $domainPopularity = [];
    private $io;

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
        $count = $this->entityManager->getRepository(News::class)->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.predictRatingV1 = :predictRatingV1')
            ->setParameter('predictRatingV1', '666')
            ->getQuery()
            ->getSingleScalarResult();
        return $count;
    }

    public function proccessNewsEntity(News $newsEntity): int
    {

        $chatGptRating = $this->getChatGptRating($newsEntity);
        $popularityScore = $this->getPopularityScore($newsEntity);
        $rating = $chatGptRating * $popularityScore;

        $this->io->info('Rating: ' . $rating);
        $this->logger->info('Ratet V1', [
            'news' => $newsEntity->getTitle(),
            'chatGptRating' => $chatGptRating,
            'popularityScore' => $popularityScore,
            'rating' => $rating
        ]);
        $newsEntity->setPredictRatingV1($rating);

        // Save the changes to the database
        $this->entityManager->flush();

        return $rating;
    }

    public function getPopularityScore(News $newsEntity): float
    {
        $domain = preg_replace('/^www\./', '', parse_url($newsEntity->getUrl(), PHP_URL_HOST));
        $newValue = ($this->getPopularityForDomain($domain) - 1) * (0.5 - 1) / (3000000 - 1) + 1;
        return $newValue;
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
        $queryBuilder = $this->entityManager->createQueryBuilder();

        $query = $queryBuilder->select('n')
            ->from(News::class, 'n')
            ->where('n.predictRatingV1 = :predictRatingV1')
            ->setParameter('predictRatingV1', 666)
            ->getQuery();

        $news = $query->getResult();

        return $news;
    }

    public function getPopularityForDomain(string $domain): int
    {
        if(!$this->domainPopularity) {
            // TODO: Put this in a service...
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

        foreach ($this->domainPopularity as $item) {
            if($item[0]===$domain) {
                return $item[1];
            }
        }

        return 0;
    }
}
