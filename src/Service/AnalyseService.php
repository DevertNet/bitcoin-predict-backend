<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

class AnalyseService
{
    private $entityManager;
    private $analyseAlgorithmus;
    private $matrix = [
        [
            'buyWhenNewsValueGte' => 180,
            'minHoldDays' => 3,
            'holdLongerOnNewSpike' => true,
        ]
    ];

    public function __construct(EntityManagerInterface $entityManager, AnalyseAlgorithmus $analyseAlgorithmus)
    {
        $this->entityManager = $entityManager;
        $this->analyseAlgorithmus = $analyseAlgorithmus;
    }

    /**
     * Get matrix result for the analieses
     *
     * @param  string $startDate Format: 2023-01-01
     * @param  string $endDate Format: 2023-01-01
     * @return array
     */
    public function getMatrix(string $startDate, string $endDate): array
    {
        return [];
    }

    //https://api.coingecko.com/api/v3/coins/bitcoin/market_chart?vs_currency=usd&days=365
}
