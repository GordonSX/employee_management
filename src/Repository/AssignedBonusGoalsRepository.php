<?php

namespace App\Repository;

use App\Entity\AssignedBonusGoals;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AssignedBonusGoals|null find($id, $lockMode = null, $lockVersion = null)
 * @method AssignedBonusGoals|null findOneBy(array $criteria, array $orderBy = null)
 * @method AssignedBonusGoals[]    findAll()
 * @method AssignedBonusGoals[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AssignedBonusGoalsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AssignedBonusGoals::class);
    }

    // /**
    //  * @return AssignedBonusGoals[] Returns an array of AssignedBonusGoals objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?AssignedBonusGoals
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
