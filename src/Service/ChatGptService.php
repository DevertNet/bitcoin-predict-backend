<?php

namespace App\Service;

use App\Entity\ChatGpt;
use Doctrine\ORM\EntityManagerInterface;

class ChatGptService
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getResult(string $query): string
    {
        $chatGptRepository = $this->em->getRepository(ChatGpt::class);
        $chatGpt = $chatGptRepository->findOneBy(['prompt' => $query]);

        if ($chatGpt) {
            // If a ChatGpt entity already exists for the query, use its cached response
            $result = $chatGpt->getResult();
        } else {
            $result = $this->fetchFromApi($query);

            // Create a new ChatGpt entity and persist it to the database
            $chatGpt = new ChatGpt();
            $chatGpt->setPrompt($query);
            $chatGpt->setResult($result);
            $chatGpt->setDate(new \DateTime());
            $this->em->persist($chatGpt);
            $this->em->flush();
        }

        // Return the response
        return $result;
    }

    public function fetchFromApi(string $query): string
    {
        $client = \OpenAI::client($_ENV['OPENAI_API_KEY']);

        $response = $client->chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'user', 'content' => $query],
            ],
        ]);

        foreach ($response->choices as $result) {
            return $result->message->content;
        }
    }
}
