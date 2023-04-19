<?php

namespace App\Service;

use DateTime;
use Exception;
use GuzzleHttp\Client;

class CoingeckoApi
{
    /**
     * Hold the raw response from the api.
     *
     * @var string
     */
    private $rawResponse = '';

    public function __construct()
    {
        $this->fetchFromApi();
    }

    /**
     * getMarketChart
     *
     * @return array
     */
    public function getMarketChart(): array
    {
        if(!$this->rawResponse) {
            throw new Exception('Coingecko raw response is empty.');
        }

        $data = json_decode($this->rawResponse, true);

        $out = [];

        foreach($data['prices'] as $item) {
            $out[] = [
                'date' => \DateTime::createFromFormat('U', $item[0] / 1000),
                'price' => (float) $item[1]
            ];
        }

        return $out;
    }

    /**
     * getPriceForDate
     *
     * @param  mixed $date
     * @return float
     */
    public function getPriceForDate(DateTime $date): float
    {
        $marketChart = $this->getMarketChart();

        foreach($marketChart as $item) {
            if($item['date']->format('Y-m-d')===$date->format('Y-m-d')) {
                return $item['price'];
            }
        }

        throw new Exception('No Price Found');
    }

    /**
     * fetchFromApi
     *
     * @return void
     */
    public function fetchFromApi(): void
    {
        if($this->rawResponse) {
            return;
        }

        if($_ENV['APP_ENV']==='test') {
            $this->rawResponse = '{
                "prices": [
                    [1577836800000, 10000],
                    [1577923200000, 10100],
                    [1578009600000, 10300],
                    [1578096000000, 10200],
                    [1578182400000, 10100],
                    [1578268800000, 10060],
                    [1578355200000, 10050],
                    [1578441600000, 10000]
                ]
            }';
        } else {
            var_dump('NOOO');
            die;
            $client = new Client();
            $response = $client->request('GET', 'https://api.coingecko.com/api/v3/coins/bitcoin/market_chart', [
                'query' => [
                    'vs_currency' => 'usd',
                    'days' => '365',
                ],
            ]);

            $this->rawResponse = $response->getBody();
        }
    }
}
