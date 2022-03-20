<?php

namespace App\Repository;

use App\Entity\BonusGoals;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method BonusGoals|null find($id, $lockMode = null, $lockVersion = null)
 * @method BonusGoals|null findOneBy(array $criteria, array $orderBy = null)
 * @method BonusGoals[]    findAll()
 * @method BonusGoals[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BonusGoalsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BonusGoals::class);
    }

    // /**
    //  * @return BonusGoals[] Returns an array of BonusGoals objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('b.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?BonusGoals
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
