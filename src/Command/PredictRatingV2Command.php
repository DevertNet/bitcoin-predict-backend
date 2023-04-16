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
    name: 'app:predict-rating-v2',
    description: 'Calc ratings for predictRatingV2 model',
)]
class PredictRatingV2Command extends PredictRatingCommandAbstract
{
    public $methodName = 'predictRatingV2';
    public $prompt = 'Forget all your previous instructions. Pretend you are a financial and crypto expert. You are a financial and crypto expert with stock recommendation experience. Answer “YES” if good news, “NO” if bad news, or “UNKNOWN” if uncertain in the first line. Don\'t answer more than that. Headline:';

    public function proccessNewsEntity(News $newsEntity): int
    {
        $chatGptRating = $this->getChatGptRating($newsEntity);
        $popularityScore = $this->getPopularityScore($newsEntity);
        $rating = $chatGptRating * $popularityScore;

        $this->io->info('Rating: ' . $rating);
        $this->logger->info('Ratet V2', [
            'news' => $newsEntity->getTitle(),
            'chatGptRating' => $chatGptRating,
            'popularityScore' => $popularityScore,
            'rating' => $rating
        ]);
        $newsEntity->setPredictRatingV2($rating);

        // Save the changes to the database
        $this->entityManager->flush();

        return $rating;
    }

    public function getPopularityScore(News $newsEntity): float
    {
        $domain = preg_replace('/^www\./', '', parse_url($newsEntity->getUrl(), PHP_URL_HOST));
        $newValue = ($this->getPopularityForDomain($domain) - 1) * (0.1 - 1) / (3000000 - 1) + 1;
        return $newValue;
    }
}
