<?php

namespace App\Controller;

use App\Entity\News;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ApiV2Controller extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/api/v2/list', name: 'app_api_v2_list')]
    public function index(): JsonResponse
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();

        $query = $queryBuilder->select('n')
            ->from(News::class, 'n')
            ->where('n.predictRatingV2 != :predictRatingV2')
            ->setParameter('predictRatingV2', 666)
            ->getQuery();

        $news = $query->getResult();

        $out = [];
        foreach ($news as $newsEntity) {
            $key = $newsEntity->getDate()->format("d.m.Y");
            if(!array_key_exists($key, $out)) {
                $out[$key] = 0;
            }

            $out[$key] += $newsEntity->getPredictRatingV2();
        }

        return $this->json($out);
    }
}
