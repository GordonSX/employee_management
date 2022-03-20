<?php

namespace App\Repository;

use App\Entity\MonthlyEmployeeCosts;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MonthlyEmployeeCosts|null find($id, $lockMode = null, $lockVersion = null)
 * @method MonthlyEmployeeCosts|null findOneBy(array $criteria, array $orderBy = null)
 * @method MonthlyEmployeeCosts[]    findAll()
 * @method MonthlyEmployeeCosts[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MonthlyEmployeeCostsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MonthlyEmployeeCosts::class);
    }

    // /**
    //  * @return MonthlyEmployeeCosts[] Returns an array of MonthlyEmployeeCosts objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('m.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?MonthlyEmployeeCosts
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
