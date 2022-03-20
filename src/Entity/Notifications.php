<?php

namespace App\Entity;

use App\Repository\NotificationsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=NotificationsRepository::class)
 */
class Notifications
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $username;

    /**
     * @ORM\Column(type="text")
     */
    private $content_of_notification;

    /**
     * @ORM\Column(type="date")
     */
    private $date_of_notification;

    /**
     * @ORM\Column(type="time")
     */
    private $time_of_notification;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $notification_preview;

    /**
     * @ORM\Column(type="boolean")
     */
    private $is_it_read;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $additional_information = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getContentOfNotification(): ?string
    {
        return $this->content_of_notification;
    }

    public function setContentOfNotification(string $content_of_notification): self
    {
        $this->content_of_notification = $content_of_notification;

        return $this;
    }

    public function getDateOfNotification(): ?\DateTimeInterface
    {
        return $this->date_of_notification;
    }

    public function setDateOfNotification(\DateTimeInterface $date_of_notification): self
    {
        $this->date_of_notification = $date_of_notification;

        return $this;
    }

    public function getTimeOfNotification(): ?\DateTimeInterface
    {
        return $this->time_of_notification;
    }

    public function setTimeOfNotification(\DateTimeInterface $time_of_notification): self
    {
        $this->time_of_notification = $time_of_notification;

        return $this;
    }

    public function getNotificationPreview(): ?string
    {
        return $this->notification_preview;
    }

    public function setNotificationPreview(string $notification_preview): self
    {
        $this->notification_preview = $notification_preview;

        return $this;
    }

    public function getIsItRead(): ?bool
    {
        return $this->is_it_read;
    }

    public function setIsItRead(bool $is_it_read): self
    {
        $this->is_it_read = $is_it_read;

        return $this;
    }

    public function getAdditionalInformation(): ?array
    {
        return $this->additional_information;
    }

    public function setAdditionalInformation(?array $additional_information): self
    {
        $this->additional_information = $additional_information;

        return $this;
    }
}
