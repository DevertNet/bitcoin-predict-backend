<?php

namespace App\Service;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\News;

class AnalyseAlgorithmus
{
    private $entityManager;
    private $coingeckoApi;

    public function __construct(EntityManagerInterface $entityManager, CoingeckoApi $coingeckoApi)
    {
        $this->entityManager = $entityManager;
        $this->coingeckoApi = $coingeckoApi;
    }

    public function getRecommendation(
        DateTime $date,
        bool $isInvested,
        int $buyPrice,
        DateTime $buyDate,
        int $buyWhenNewsValueGte,
        int $minHoldDays,
        bool $holdLongerOnNewSpike
    ): string {
        // Clean Dates
        $date->setTime(0, 0, 0);
        $buyDate->setTime(0, 0, 0);

        // Get Data
        $currentPrice = (int) $this->coingeckoApi->getPriceForDate($date);
        $currentRating = $this->entityManager->getRepository(News::class)->getPredictRatingV2ForDate($date);
        $hasSpikeAfterBuy = $this->hasSpikeAfterBuy($buyDate, $minHoldDays, $buyWhenNewsValueGte);
        $spikeDate = $this->getSpikeDateAfterBuy($buyDate, $minHoldDays, $buyWhenNewsValueGte);
        $exitDate = (clone $buyDate)->modify('+'.$minHoldDays.' days');

        // If we have a spike after buy: reset buydate to spike date
        // But only if not older than current date...
        if($hasSpikeAfterBuy && $spikeDate<=$date) {
            $buyDate = $this->getSpikeDateAfterBuy($buyDate, $minHoldDays, $buyWhenNewsValueGte);
            $exitDate = (clone $buyDate)->modify('+'.$minHoldDays.' days');
        }

        if(!$isInvested && $currentRating<$buyWhenNewsValueGte) {
            return 'WAIT';
        } elseif(!$isInvested && $currentRating>=$buyWhenNewsValueGte) {
            return 'BUY';
        } elseif($isInvested && $currentRating>=$buyWhenNewsValueGte) {
            return 'HOLD_RESET';
        } elseif($isInvested && $date > $buyDate && $currentPrice > $buyPrice) {
            return 'SELL_POSITIVE';
        } elseif($isInvested && $date > $buyDate && $date >= $exitDate && $currentPrice <= $buyPrice) {
            return 'SELL_NEGATIV';
        } elseif($isInvested && $date > $buyDate && $currentPrice < $buyPrice) {
            return 'HOLD';
        }

        // Date       Score Price
        // 2020-01-01 15    10000 WAIT
        // 2020-01-02 20    10100 BUY
        // 2020-01-03 15    10300 SELL
        // 2020-01-04 20    10200 BUY
        // 2020-01-05 25    10100 HOLD_RESET
        // 2020-01-06 15    10060 HOLD
        // 2020-01-07 15    10050 SELL
        // 2020-01-08 15    10000 WAIT

        return 'UNKNOWN';
    }

    /**
     * hasSpikeAfterBuy
     *
     * @param  mixed $buyDate
     * @param  mixed $minHoldDays
     * @param  mixed $buyWhenNewsValueGte
     * @return bool
     */
    public function hasSpikeAfterBuy(DateTime $buyDate, int $minHoldDays, int $buyWhenNewsValueGte): bool
    {
        $spikeDate = $this->getSpikeDateAfterBuy($buyDate, $minHoldDays, $buyWhenNewsValueGte);

        if($spikeDate->format('Y-m-d')===$buyDate->format('Y-m-d')) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Return a spike date after buy. If no spike found the buy date will be returned
     *
     * @param  mixed $buyDate
     * @param  mixed $minHoldDays
     * @param  mixed $buyWhenNewsValueGte
     * @return DateTime
     */
    public function getSpikeDateAfterBuy(DateTime $buyDate, int $minHoldDays, int $buyWhenNewsValueGte): DateTime
    {
        $endDate = (clone $buyDate)->modify('+'.($minHoldDays+1).' days');
        $date = (clone $buyDate)->modify('+1 days');
        while ($date <= $endDate) {
            $rating = $this->entityManager->getRepository(News::class)->getPredictRatingV2ForDate($date);
            if($rating>=$buyWhenNewsValueGte) {
                return $date;
            }
            $date->modify('+1 day');
        }

        return $buyDate;
    }
}
