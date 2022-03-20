<?php

namespace App\Entity;

use App\Repository\AssignedBonusGoalsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=AssignedBonusGoalsRepository::class)
 */
class AssignedBonusGoals
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
     * @ORM\Column(type="array", nullable=true)
     */
    private $completion_percentage = [];

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $comment = [];

    /**
     * @ORM\Column(type="date")
     */
    private $period;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $progress;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $acceptance_path = [];

    /**
     * @ORM\ManyToOne(targetEntity=BonusGoals::class, inversedBy="assignedBonusGoals")
     * @ORM\JoinColumn(nullable=false)
     */
    private $bonus_goal;

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

    public function getCompletionPercentage(): ?array
    {
        return $this->completion_percentage;
    }

    public function setCompletionPercentage(?array $completion_percentage): self
    {
        $this->completion_percentage = $completion_percentage;

        return $this;
    }

    public function getComment(): ?array
    {
        return $this->comment;
    }

    public function setComment(?array $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getPeriod(): ?\DateTimeInterface
    {
        return $this->period;
    }

    public function setPeriod(\DateTimeInterface $period): self
    {
        $this->period = $period;

        return $this;
    }

    public function getProgress(): ?string
    {
        return $this->progress;
    }

    public function setProgress(string $progress): self
    {
        $this->progress = $progress;

        return $this;
    }

    public function getAcceptancePath(): ?array
    {
        return $this->acceptance_path;
    }

    public function setAcceptancePath(?array $acceptance_path): self
    {
        $this->acceptance_path = $acceptance_path;

        return $this;
    }

    public function getBonusGoal(): ?BonusGoals
    {
        return $this->bonus_goal;
    }

    public function setBonusGoal(?BonusGoals $bonus_goal): self
    {
        $this->bonus_goal = $bonus_goal;

        return $this;
    }
}
