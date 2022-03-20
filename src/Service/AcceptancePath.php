<?php

namespace App\Service;

use App\Entity\AssignedBonusGoals;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

class AcceptancePath
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;
    }

    public function setAcceptancePath($employeeUsername, $userMakingAction , $period, $message): void
    {
        $assignedBonusGoals = $this->entityManager->getRepository(AssignedBonusGoals::class)->findOneBy(['username'=>$employeeUsername,'period'=>$period]);

        $acceptancePathArray = $assignedBonusGoals->getAcceptancePath();
        $date = new DateTime('now');
        $acceptancePathArray[$date->format('Y-m-d H:i:s')] = $userMakingAction.' '.$message;
        $assignedBonusGoals->setAcceptancePath($acceptancePathArray);
        $this->entityManager->persist($assignedBonusGoals);
        $this->entityManager->flush();

    }
}