<?php

namespace App\Service\NewsApi;

use Exception;
use DateTime;
use App\Entity\ChatGpt;

class ApiMediastack extends ApiAbstract
{
    public $limit = 100;

    public function getNewsForDate(DateTime $date, int $page = 1): array
    {
        try {
            $response = $this->call('GET', 'http://api.mediastack.com/v1/news', [
                    'access_key' => $_ENV['MEDIASTACK_API_KEY'],
                    'languages' => 'en',
                    'categories' => 'general,technology,business', // TODO: Save category to news entity
                    //'keywords' => 'crypto bitcoin',
                    'date' => $date->format('Y-m-d'),
                    'offset' => ($page - 1) * $this->limit,
                    'limit' => $this->limit
                ], $date);
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

        return $this->response['pagination']['total'];
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
            $newsRequestItem->setCategory($rawItem['category']);

            $out[] = $newsRequestItem;
        }

        return $out;
    }



}
