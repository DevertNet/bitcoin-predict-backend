<?php

namespace App\Service\NewsApi;

use Exception;
use DateTime;
use App\Entity\ChatGpt;

class ApiTheNewsApi extends ApiAbstract
{
    public $limit = 25;

    public function getNewsForDate(DateTime $date, int $page = 1): array
    {
        try {
            $response = $this->call('GET', 'https://api.thenewsapi.com/v1/news/all', [
                    'api_token' => $_ENV['THENEWSAPI_API_KEY'],
                    'language' => 'en',
                    'categories' => 'general,business,tech,politics',
                    'exclude_categories' => 'sports',
                    'domains' => 'nytimes.com,cnn.com,bbc.co.uk,theguardian.com',
                    'search' => '-sport+-museums',
                    'published_on' => $date->format('Y-m-d'),
                    'page' => $page,
                    'limit' => $this->limit
                ]);
            return $this->getItems();
        } catch (\Exception $th) {
            $this->logger->error('Cant fetch data from api: ', [$th->getMessage()]);
        }

        return [];
    }

    public function getTotalCount(): int
    {
        if(!$this->response) {
            throw new Exception('Please make a request first. Response is empty.');
        }

        return $this->response['meta']['found'];
    }

    public function getItems(): array
    {
        if(!$this->response) {
            throw new Exception('Please make a request first. Response is empty.');
        }

        $out = [];
        foreach ($this->response['data'] as $rawItem) {
            $newsRequestItem = new NewsRequestItem();
            $newsRequestItem->setTitle($rawItem['title']);
            $newsRequestItem->setText($rawItem['description']);
            $newsRequestItem->setUrl($rawItem['url']);
            $newsRequestItem->setDate(new \DateTime($rawItem['published_at']));
            $newsRequestItem->setCategory(implode(',', $rawItem['categories']));

            $out[] = $newsRequestItem;
        }

        return $out;
    }



}
