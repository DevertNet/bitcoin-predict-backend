<?php

namespace App\Service\NewsApi;

use Exception;
use DateTime;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\NewsApiRequests;
use GuzzleHttp\Client;

abstract class ApiAbstract
{
    public $limit = 10;
    public $entityManager;
    public $logger;
    public $client;
    public $response;

    public function __construct(LoggerInterface $logger, EntityManagerInterface $entityManager)
    {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->client = new Client();
    }

    public function call(string $method, string $url, array $query): array
    {
        $hash = md5(implode('', [$method, $url, serialize($query)]));


        $newsApiRequest = $this->getNewsApiRequestByHash($hash);
        if($newsApiRequest) {
            // Return cached data
            $response = json_decode($newsApiRequest->getResponse());
        } else {
            // fetch data from api
            $response = $this->client->request($method, $url, [
                'query' => $query
            ]);

            // Safe data in cache
            $entity = new NewsApiRequest();
            $entity->setHash($hash);
            $entity->setUrl($url);
            $entity->setQuery(json_encode($query));
            $entity->setResponse($response->getBody());
            $this->em->persist($entity);
            $this->em->flush();

            $response = json_decode($response->getBody(), true);
        }

        $this->response = $response;
        return $this->response;
    }

    private function getNewsApiRequestByHash(string $hash)
    {
        $newsApiRequest = $this->entityManager->getRepository(NewsApiRequests::class)->findOneBy([
            'hash' => $hash,
        ]);

        return $newsApiRequest;
    }

    public function getTotalPages(): int
    {
        if(!$this->response) {
            throw new Exception('Please make a request first. Response is empty.');
        }

        return ceil($this->getTotalCount() / $this->limit);
    }
}
