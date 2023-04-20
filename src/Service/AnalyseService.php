<?php

namespace App\Service;

use DateTime;
use App\Entity\News;
use Doctrine\ORM\EntityManagerInterface;

class AnalyseService
{
    private $entityManager;
    private $analyseAlgorithmus;
    private $coingeckoApi;
    /**
     * 01.01.2023 - 18.04.2023
     * 200 3 true  = 1010.10
     * 200 2 true  = 971.47
     * 200 2 false = 1065.50
     * 180 2 true  = 1251.30
     * 180 2 false = 1158.24
     * 180 1 true  = 1165.09
     * 180 1 false = 1070.33
     */
    public $matrix = [
        [
            'buyWhenNewsValueGte' => 200,
            'minHoldDays' => 2,
            'holdLongerOnNewSpike' => true,
        ],
        [
            'buyWhenNewsValueGte' => 200,
            'minHoldDays' => 2,
            'holdLongerOnNewSpike' => false,
        ]
    ];

    public function __construct(EntityManagerInterface $entityManager, AnalyseAlgorithmus $analyseAlgorithmus, CoingeckoApi $coingeckoApi)
    {
        $this->entityManager = $entityManager;
        $this->analyseAlgorithmus = $analyseAlgorithmus;
        $this->coingeckoApi = $coingeckoApi;
    }

    /**
     * Get matrix result for the analieses
     *
     * @param  string $startDate Format: 2023-01-01
     * @param  string $endDate Format: 2023-01-01
     * @return array
     */
    public function getMatrix(string $startDate, string $endDate, float $investInFiat): array
    {
        $out = [];
        foreach($this->matrix as $instanceOptions) {
            $out[] = $this->proccessInstance(
                (new DateTime($startDate))->setTime(0, 0, 0),
                (new DateTime($endDate))->setTime(0, 0, 0),
                $instanceOptions['buyWhenNewsValueGte'],
                $instanceOptions['minHoldDays'],
                $instanceOptions['holdLongerOnNewSpike'],
                $investInFiat
            );
        }

        return $out;
    }

    public function proccessInstance(
        DateTime $startDate,
        DateTime $endDate,
        int $buyWhenNewsValueGte,
        int $minHoldDays,
        bool $holdLongerOnNewSpike,
        float $investInFiat
    ): array {
        $out = [];
        $bitcoinBalance = 0;
        $fiatBalance = $investInFiat;
        $buyPrice = 0;
        $buyDate = new DateTime('now');

        $date = (clone $startDate);
        while ($date <= $endDate) {
            $isInvested = $bitcoinBalance > 0;
            $bitcoinPrice = $this->coingeckoApi->getPriceForDate($date);

            // Value can be 'WAIT'|'BUY'|'HOLD'|'SELL_POSITIVE'|'SELL_NEGATIV'|'HOLD_RESET'|'UNKNOWN'
            $advise = $this->analyseAlgorithmus->getRecommendation(
                $date,
                $isInvested,
                $buyPrice,
                $buyDate,
                $buyWhenNewsValueGte,
                $minHoldDays,
                $holdLongerOnNewSpike
            );

            if(in_array($advise, ['BUY'])) {
                $bitcoinBalance = (1/$bitcoinPrice)*$fiatBalance;
                $fiatBalance = 0;
                $buyDate = clone $date;
                $buyPrice = $bitcoinPrice;
            } elseif(in_array($advise, ['SELL_POSITIVE', 'SELL_NEGATIV'])) {
                $fiatBalance = $bitcoinBalance * $bitcoinPrice;
                $bitcoinBalance = 0;
            }

            $out[] = [
                'date' => clone $date,
                'price' => $bitcoinPrice,
                'score' => $this->entityManager->getRepository(News::class)->getPredictRatingV2ForDate($date),
                'advise' => $advise,
                'fiatBalanceAfterAdvise' => $fiatBalance,
                'bitcoinBalanceAfterAdvise' => $bitcoinBalance,
            ];

            $date->modify('+1 day');
        }

        return $out;
    }
}
