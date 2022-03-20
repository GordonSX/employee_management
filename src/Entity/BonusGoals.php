<?php

namespace App\Entity;

use App\Repository\BonusGoalsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=BonusGoalsRepository::class)
 */
class BonusGoals
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="array")
     */
    private $target = [];

    /**
     * @ORM\Column(type="array")
     */
    private $expected_value = [];

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $assigned_to;

    /**
     * @ORM\Column(type="date")
     */
    private $period_date;

    /**
     * @ORM\OneToMany(targetEntity=AssignedBonusGoals::class, mappedBy="bonus_goal", orphanRemoval=true)
     */
    private $assignedBonusGoals;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $added_by;

    public function __construct()
    {
        $this->assignedBonusGoals = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTarget(): ?array
    {
        return $this->target;
    }

    public function setTarget(array $target): self
    {
        $this->target = $target;

        return $this;
    }

    public function getExpectedValue(): ?array
    {
        return $this->expected_value;
    }

    public function setExpectedValue(array $expected_value): self
    {
        $this->expected_value = $expected_value;

        return $this;
    }

    public function getAssignedTo(): ?string
    {
        return $this->assigned_to;
    }

    public function setAssignedTo(?string $assigned_to): self
    {
        $this->assigned_to = $assigned_to;

        return $this;
    }

    public function getPeriodDate(): ?\DateTimeInterface
    {
        return $this->period_date;
    }

    public function setPeriodDate(\DateTimeInterface $period_date): self
    {
        $this->period_date = $period_date;

        return $this;
    }

    /**
     * @return Collection|AssignedBonusGoals[]
     */
    public function getAssignedBonusGoals(): Collection
    {
        return $this->assignedBonusGoals;
    }

    public function addAssignedBonusGoal(AssignedBonusGoals $assignedBonusGoal): self
    {
        if (!$this->assignedBonusGoals->contains($assignedBonusGoal)) {
            $this->assignedBonusGoals[] = $assignedBonusGoal;
            $assignedBonusGoal->setBonusGoal($this);
        }

        return $this;
    }

    public function removeAssignedBonusGoal(AssignedBonusGoals $assignedBonusGoal): self
    {
        if ($this->assignedBonusGoals->removeElement($assignedBonusGoal)) {
            // set the owning side to null (unless already changed)
            if ($assignedBonusGoal->getBonusGoal() === $this) {
                $assignedBonusGoal->setBonusGoal(null);
            }
        }

        return $this;
    }

    public function getAddedBy(): ?string
    {
        return $this->added_by;
    }

    public function setAddedBy(string $added_by): self
    {
        $this->added_by = $added_by;

        return $this;
    }

}
