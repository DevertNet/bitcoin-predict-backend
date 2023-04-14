<?php

namespace App\Command;

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
    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $entityManager = $this->getDoctrine()->getManager();
        foreach ($this->getDataFromApi() as $newsItem) {
            $io->note(sprintf('Start add news: %s', $newsItem['title']));

            try {
                $news = new News();
                $news->setTitle($newsItem['title']);
                $news->setText($newsItem['description']);
                $news->setUrl($newsItem['url']);
                $news->setDate(new \DateTime($newsItem['published_at']));
                $news->setPredictRatingV1(0);
                $entityManager->persist($news);

                $io->success('DONE');
            } catch (\Exception $th) {
                $io->error('ERROR');
            }
        }
        $entityManager->flush();

        return Command::SUCCESS;
    }

    protected function getDataFromApi() {
        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', 'http://api.mediastack.com/v1/news', [
            'query' => [
                'access_key' => $_ENV['MEDIASTACK_API_KEY'],
                'languages' => 'en',
                'keywords' => 'technology',
                'limit' => 10
            ]
        ]);
        $data = json_decode($response->getBody(), true);
        return $data['data'];
    }
}
