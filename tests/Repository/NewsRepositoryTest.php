<?php

namespace App\Tests\Repository;

use App\Repository\NewsRepository;
use App\Tests\AppTestCase;
use DateTime;

class NewsRepositoryTest extends AppTestCase
{
    public function test_getPredictRatingV2ForDate()
    {
        /** @var NewsRepository */
        $newsRepository = $this->container->get(NewsRepository::class);

        $value = $newsRepository->getPredictRatingV2ForDate(new DateTime('2020-01-01'));
        $this->assertEquals(15, $value);
    }
}
