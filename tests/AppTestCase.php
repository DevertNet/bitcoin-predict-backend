<?php

namespace App\Tests;

use App\Entity\News;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManagerInterface;

abstract class AppTestCase extends KernelTestCase
{
    public $container;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->container = static::getContainer();
        $this->addTestNews();
    }

    /**
     * Add a few test news for the tests
     *
     * @return void
     */
    protected function addTestNews()
    {
        $this->removeTestNews();

        $entityManager = $this->container->get(EntityManagerInterface::class);

        // Date       Score Price
        // 2020-01-01 15    10000 WAIT
        // 2020-01-02 20    10100 BUY
        // 2020-01-03 15    10300 SELL
        // 2020-01-04 20    10200 BUY
        // 2020-01-05 25    10100 HOLD RESET
        // 2020-01-06 15    10060 HOLD
        // 2020-01-07 15    10050 SELL
        // 2020-01-08 15    10000 WAIT
        $newsPrototype = [
            [ 'date' => "2020-01-01", 'score' => 10 ],
            [ 'date' => "2020-01-01", 'score' => 10 ],
            [ 'date' => "2020-01-01", 'score' => -5 ],

            [ 'date' => "2020-01-02", 'score' => 10 ],
            [ 'date' => "2020-01-02", 'score' => 10 ],

            [ 'date' => "2020-01-03", 'score' => 10 ],
            [ 'date' => "2020-01-03", 'score' => 5 ],

            [ 'date' => "2020-01-04", 'score' => 10 ],
            [ 'date' => "2020-01-04", 'score' => 10 ],

            [ 'date' => "2020-01-05", 'score' => 10 ],
            [ 'date' => "2020-01-05", 'score' => 10 ],
            [ 'date' => "2020-01-05", 'score' => 5 ],

            [ 'date' => "2020-01-06", 'score' => 10 ],
            [ 'date' => "2020-01-06", 'score' => 5 ],

            [ 'date' => "2020-01-07", 'score' => 10 ],
            [ 'date' => "2020-01-07", 'score' => 5 ],

            [ 'date' => "2020-01-08", 'score' => 10 ],
            [ 'date' => "2020-01-08", 'score' => 5 ],
        ];

        $index = 0;
        foreach($newsPrototype as $newsPrototypeItem) {
            $index++;
            $news = new News();
            $news->setTitle('Demo '.$index.' Title');
            $news->setText('Demo '.$index.' Text');
            $news->setUrl('http://example.com/'.$index);
            $news->setDate(new DateTime($newsPrototypeItem['date']));
            $news->setCategory('test');
            $news->setPredictRatingV2($newsPrototypeItem['score']);
            $entityManager->persist($news);
        }

        $entityManager->flush();
    }

    protected function removeTestNews()
    {
        $entityManager = $this->container->get(EntityManagerInterface::class);
        $entityManager->getRepository(News::class)->createQueryBuilder('n')
            ->delete(News::class, 'n')
            ->andWhere('n.date >= :startDate')
            ->setParameter('startDate', (new DateTime('2020-01-01'))->setTime(0, 0, 0))
            ->andWhere('n.date < :endDate')
            ->setParameter('endDate', (new DateTime('2020-01-20'))->setTime(23, 59, 59))
            ->getQuery()
            ->execute();
    }

    protected function tearDown(): void
    {
        $this->removeTestNews();
    }
}
