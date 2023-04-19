<?php

namespace App\Repository;

use App\Entity\News;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<News>
 *
 * @method News|null find($id, $lockMode = null, $lockVersion = null)
 * @method News|null findOneBy(array $criteria, array $orderBy = null)
 * @method News[]    findAll()
 * @method News[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NewsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, News::class);
    }

    public function save(News $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(News $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Get the predict value for a specific date for predictRatingV2
     *
     * @param  mixed $date
     * @return int
     */
    public function getPredictRatingV2ForDate(DateTime $date): int
    {
        $query = $this->createQueryBuilder('n')
            ->where('n.predictRatingV2 != :predictRatingV2')
            ->setParameter('predictRatingV2', 666)
            ->andWhere('n.date >= :startDate')
            ->setParameter('startDate', (clone $date)->setTime(0, 0, 0))
            ->andWhere('n.date <= :endDate')
            ->setParameter('endDate', (clone $date)->setTime(23, 59, 59))
            ->getQuery();

        $news = $query->getResult();

        $value = 0;
        foreach ($news as $newsEntity) {
            $value += $newsEntity->getPredictRatingV2();
        }

        return $value;
    }
//    /**
//     * @return News[] Returns an array of News objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('n')
//            ->andWhere('n.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('n.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?News
//    {
//        return $this->createQueryBuilder('n')
//            ->andWhere('n.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
