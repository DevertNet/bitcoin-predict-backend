<?php

namespace App\Controller;

use App\Service\AnalyseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class AnalyseController extends AbstractController
{
    private $analyseService;

    public function __construct(AnalyseService $analyseService)
    {
        $this->analyseService = $analyseService;
    }

    #[Route('/analyse', name: 'app_analyse')]
    public function index(): JsonResponse
    {
        return $this->json($this->analyseService->getMatrix('2022-08-14', '2022-12-14', 1000));
    }
}
