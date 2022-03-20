<?php

namespace App\Service;

use App\Entity\Notifications;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;
    }

    public function getNumberOfNotifications($username): int
    {
        $notifications = $this->entityManager->getRepository(Notifications::class)->findBy(['username'=>$username]);
        $counter = 0;
        foreach ($notifications as $notification) {
            if (!$notification->getIsItRead()){
                $counter++;
            }
        }
        return $counter;
    }

    public function setNotification($username, $content_of_notification, $notificationPreview, $additional_information): void
    {
        $dateTime = new DateTime('now');

        $notifications = new Notifications();
        $notifications->setUsername($username);
        $notifications->setContentOfNotification($content_of_notification);
        $notifications->setNotificationPreview($notificationPreview);
        $notifications->setDateOfNotification($dateTime);
        $notifications->setTimeOfNotification($dateTime);
        $notifications->setIsItRead(false);
        $notifications->setAdditionalInformation($additional_information);

        $this->entityManager->persist($notifications);
        $this->entityManager->flush();
    }
}