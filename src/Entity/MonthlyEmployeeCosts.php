<?php

namespace App\Entity;

use App\Repository\MonthlyEmployeeCostsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=MonthlyEmployeeCostsRepository::class)
 */
class MonthlyEmployeeCosts
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="float")
     */
    private $pension_insurance;

    /**
     * @ORM\Column(type="float")
     */
    private $disability_insurance;

    /**
     * @ORM\Column(type="float")
     */
    private $medical_insurance;

    /**
     * @ORM\Column(type="float")
     */
    private $insurance_in_case_of_illness;

    /**
     * @ORM\Column(type="float")
     */
    private $advance_payment_for_PIT;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPensionInsurance(): ?float
    {
        return $this->pension_insurance;
    }

    public function setPensionInsurance(float $pension_insurance): self
    {
        $this->pension_insurance = $pension_insurance;

        return $this;
    }

    public function getDisabilityInsurance(): ?float
    {
        return $this->disability_insurance;
    }

    public function setDisabilityInsurance(float $disability_insurance): self
    {
        $this->disability_insurance = $disability_insurance;

        return $this;
    }

    public function getMedicalInsurance(): ?float
    {
        return $this->medical_insurance;
    }

    public function setMedicalInsurance(float $medical_insurance): self
    {
        $this->medical_insurance = $medical_insurance;

        return $this;
    }

    public function getInsuranceInCaseOfIllness(): ?float
    {
        return $this->insurance_in_case_of_illness;
    }

    public function setInsuranceInCaseOfIllness(float $insurance_in_case_of_illness): self
    {
        $this->insurance_in_case_of_illness = $insurance_in_case_of_illness;

        return $this;
    }

    public function getAdvancePaymentForPIT(): ?float
    {
        return $this->advance_payment_for_PIT;
    }

    public function setAdvancePaymentForPIT(float $advance_payment_for_PIT): self
    {
        $this->advance_payment_for_PIT = $advance_payment_for_PIT;

        return $this;
    }
}
