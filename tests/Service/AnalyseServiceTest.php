<?php

namespace App\Tests\Service;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Service\AnalyseService;

class AnalyseServiceTest extends KernelTestCase
{
    private $container;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->container = static::getContainer();
    }

    public function testSomething()
    {
        /** @var AnalyseService */
        $analyseService = $this->container->get(AnalyseService::class);
        $analyseService->matrix = [
            [
                'buyWhenNewsValueGte' => 20,
                'minHoldDays' => 2,
                'holdLongerOnNewSpike' => true,
            ]
        ];

        $expected = '
        [
            [
              {
                "date": {
                  "date": "2020-01-01 00:00:00.000000",
                  "timezone_type": 3,
                  "timezone": "UTC"
                },
                "price": 10000,
                "score": 15,
                "advise": "WAIT",
                "fiatBalanceAfterAdvise": 1000,
                "bitcoinBalanceAfterAdvise": 0
              },
              {
                "date": {
                  "date": "2020-01-02 00:00:00.000000",
                  "timezone_type": 3,
                  "timezone": "UTC"
                },
                "price": 10100,
                "score": 20,
                "advise": "BUY",
                "fiatBalanceAfterAdvise": 0,
                "bitcoinBalanceAfterAdvise": 0.09900990099009901
              },
              {
                "date": {
                  "date": "2020-01-03 00:00:00.000000",
                  "timezone_type": 3,
                  "timezone": "UTC"
                },
                "price": 10300,
                "score": 15,
                "advise": "SELL_POSITIVE",
                "fiatBalanceAfterAdvise": 1019.8019801980198,
                "bitcoinBalanceAfterAdvise": 0
              },
              {
                "date": {
                  "date": "2020-01-04 00:00:00.000000",
                  "timezone_type": 3,
                  "timezone": "UTC"
                },
                "price": 10200,
                "score": 20,
                "advise": "BUY",
                "fiatBalanceAfterAdvise": 0,
                "bitcoinBalanceAfterAdvise": 0.0999805862939235
              },
              {
                "date": {
                  "date": "2020-01-05 00:00:00.000000",
                  "timezone_type": 3,
                  "timezone": "UTC"
                },
                "price": 10100,
                "score": 25,
                "advise": "HOLD_RESET",
                "fiatBalanceAfterAdvise": 0,
                "bitcoinBalanceAfterAdvise": 0.0999805862939235
              },
              {
                "date": {
                  "date": "2020-01-06 00:00:00.000000",
                  "timezone_type": 3,
                  "timezone": "UTC"
                },
                "price": 10060,
                "score": 15,
                "advise": "HOLD",
                "fiatBalanceAfterAdvise": 0,
                "bitcoinBalanceAfterAdvise": 0.0999805862939235
              },
              {
                "date": {
                  "date": "2020-01-07 00:00:00.000000",
                  "timezone_type": 3,
                  "timezone": "UTC"
                },
                "price": 10050,
                "score": 15,
                "advise": "SELL_NEGATIV",
                "fiatBalanceAfterAdvise": 1004.8048922539313,
                "bitcoinBalanceAfterAdvise": 0
              },
              {
                "date": {
                  "date": "2020-01-08 00:00:00.000000",
                  "timezone_type": 3,
                  "timezone": "UTC"
                },
                "price": 10000,
                "score": 15,
                "advise": "WAIT",
                "fiatBalanceAfterAdvise": 1004.8048922539313,
                "bitcoinBalanceAfterAdvise": 0
              }
            ]
          ]
        ';

        $actual = $analyseService->getMatrix('2020-01-01', '2020-01-08', 1000);
        $actual = json_decode(json_encode($actual), true);
        $this->assertEquals(json_decode($expected, true), $actual);
    }
}
