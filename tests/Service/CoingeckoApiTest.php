<?php

namespace App\Tests\Service;

use App\Tests\AppTestCase;
use App\Service\CoingeckoApi;
use DateTime;

class CoingeckoApiTest extends AppTestCase
{
    public function test_getMarketChart()
    {
        /** @var CoingeckoApi */
        $analyseService = $this->container->get(CoingeckoApi::class);
        $marketChart = $analyseService->getMarketChart();


        $this->assertEquals(10000.0, $marketChart[0]['price']);
        $this->assertEquals('2020-01-01', $marketChart[0]['date']->format('Y-m-d'));
    }

    public function test_getPriceForDate()
    {
        /** @var CoingeckoApi */
        $analyseService = $this->container->get(CoingeckoApi::class);
        $price = $analyseService->getPriceForDate(new DateTime('2020-01-02'));

        $this->assertEquals(10100, $price);
    }
}
