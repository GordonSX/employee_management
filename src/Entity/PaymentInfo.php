<?php

namespace App\Entity;

use App\Repository\PaymentInfoRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PaymentInfoRepository::class)
 */
class PaymentInfo
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id;

    /**
     * @ORM\Column(type="string", length=180)
     */
    private ?string $username;

    /**
     * @ORM\Column(type="array")
     */
    private array $basic_salary = [];

    /**
     * @ORM\Column(type="array")
     */
    private array $bonus_salary = [];

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

    public function getBasicSalary(): ?array
    {
        return $this->basic_salary;
    }

    public function setBasicSalary(array $basic_salary): self
    {
        $this->basic_salary = $basic_salary;

        return $this;
    }

    public function getBonusSalary(): ?array
    {
        return $this->bonus_salary;
    }

    public function setBonusSalary(array $bonus_salary): self
    {
        $this->bonus_salary = $bonus_salary;

        return $this;
    }
}
