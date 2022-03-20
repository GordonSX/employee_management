<?php

namespace App\Repository;

use App\Entity\NonWorkingDays;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method NonWorkingDays|null find($id, $lockMode = null, $lockVersion = null)
 * @method NonWorkingDays|null findOneBy(array $criteria, array $orderBy = null)
 * @method NonWorkingDays[]    findAll()
 * @method NonWorkingDays[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NonWorkingDaysRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NonWorkingDays::class);
    }

    // /**
    //  * @return NonWorkingDays[] Returns an array of NonWorkingDays objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('n.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?NonWorkingDays
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
