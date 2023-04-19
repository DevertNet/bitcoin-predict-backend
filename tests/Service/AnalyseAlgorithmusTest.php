<?php

namespace App\Tests\Service;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Service\AnalyseAlgorithmus;
use App\Tests\AppTestCase;
use DateTime;

class AnalyseAlgorithmusTest extends AppTestCase
{
    public function test_getRecommendation()
    {
        /** @var AnalyseAlgorithmus */
        $analyseAlgorithmus = $this->container->get(AnalyseAlgorithmus::class);

        // Date       Score Price
        // 2020-01-01 15    10000 WAIT
        // 2020-01-02 20    10100 BUY
        // 2020-01-03 15    10300 SELL
        // 2020-01-04 20    10200 BUY
        // 2020-01-05 25    10100 HOLD_RESET
        // 2020-01-06 15    10060 HOLD
        // 2020-01-07 15    10050 SELL
        // 2020-01-08 15    10000 WAIT

        $this->assertEquals('WAIT', $analyseAlgorithmus->getRecommendation(
            new DateTime('2020-01-01'), // currentDate
            0, // isInvested
            0, // buyPrice
            new DateTime('2020-01-01'), // buyDate
            20, // buyWhenNewsValueGte
            2, // minHoldDays
            1 // holdLongerOnNewSpike
        ));

        $this->assertEquals('BUY', $analyseAlgorithmus->getRecommendation(
            new DateTime('2020-01-02'), // currentDate
            0, // isInvested
            0, // buyPrice
            new DateTime('2020-01-01'), // buyDate
            20, // buyWhenNewsValueGte
            2, // minHoldDays
            1 // holdLongerOnNewSpike
        ));

        $this->assertEquals('SELL_POSITIVE', $analyseAlgorithmus->getRecommendation(
            new DateTime('2020-01-03'), // currentDate
            1, // isInvested
            10100, // buyPrice
            new DateTime('2020-01-02'), // buyDate
            20, // buyWhenNewsValueGte
            2, // minHoldDays
            1 // holdLongerOnNewSpike
        ), 'SELL Error on 2020-01-03');

        $this->assertEquals('BUY', $analyseAlgorithmus->getRecommendation(
            new DateTime('2020-01-04'), // currentDate
            0, // isInvested
            10100, // buyPrice
            new DateTime('2020-01-02'), // buyDate
            20, // buyWhenNewsValueGte
            2, // minHoldDays
            1 // holdLongerOnNewSpike
        ));

        $this->assertEquals('HOLD_RESET', $analyseAlgorithmus->getRecommendation(
            new DateTime('2020-01-05'), // currentDate
            1, // isInvested
            10200, // buyPrice
            new DateTime('2020-01-04'), // buyDate
            20, // buyWhenNewsValueGte
            2, // minHoldDays
            1 // holdLongerOnNewSpike
        ));

        $this->assertEquals('HOLD', $analyseAlgorithmus->getRecommendation(
            new DateTime('2020-01-06'), // currentDate
            1, // isInvested
            10200, // buyPrice
            new DateTime('2020-01-04'), // buyDate
            20, // buyWhenNewsValueGte
            2, // minHoldDays
            1 // holdLongerOnNewSpike
        ));

        $this->assertEquals('SELL_NEGATIV', $analyseAlgorithmus->getRecommendation(
            new DateTime('2020-01-07'), // currentDate
            1, // isInvested
            10200, // buyPrice
            new DateTime('2020-01-04'), // buyDate
            20, // buyWhenNewsValueGte
            2, // minHoldDays
            1 // holdLongerOnNewSpike
        ), 'SELL Error on 2020-01-07');

        $this->assertEquals('WAIT', $analyseAlgorithmus->getRecommendation(
            new DateTime('2020-01-08'), // currentDate
            0, // isInvested
            10200, // buyPrice
            new DateTime('2020-01-04'), // buyDate
            20, // buyWhenNewsValueGte
            2, // minHoldDays
            1 // holdLongerOnNewSpike
        ));
    }

    public function test_hasSpikeAfterBuy()
    {
        /** @var AnalyseAlgorithmus */
        $analyseAlgorithmus = $this->container->get(AnalyseAlgorithmus::class);

        $this->assertEquals(true, $analyseAlgorithmus->hasSpikeAfterBuy(new DateTime('2020-01-01'), 2, 20));
        $this->assertEquals(false, $analyseAlgorithmus->hasSpikeAfterBuy(new DateTime('2020-01-06'), 2, 20));
    }

    public function test_getSpikeDateAfterBuy()
    {
        /** @var AnalyseAlgorithmus */
        $analyseAlgorithmus = $this->container->get(AnalyseAlgorithmus::class);

        $spikeDate = $analyseAlgorithmus->getSpikeDateAfterBuy(new DateTime('2020-01-01'), 2, 20);
        $this->assertEquals('2020-01-02', $spikeDate->format('Y-m-d'));

        $spikeDate = $analyseAlgorithmus->getSpikeDateAfterBuy(new DateTime('2020-01-06'), 2, 20);
        $this->assertEquals('2020-01-06', $spikeDate->format('Y-m-d'));
    }
}
