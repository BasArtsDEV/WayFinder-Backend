<?php

namespace App\Entity;

use App\Repository\SubmissionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubmissionRepository::class)]
class Submission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $submittedValue;

    #[ORM\Column]
    private bool $isRight = false;

    #[ORM\Column]
    private int $resolutionTime = 0;

    #[ORM\Column]
    private int $appliedPenalty = 0;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $submissionDate;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubmittedValue(): ?string
    {
        return $this->submittedValue;
    }

    public function setSubmittedValue(string $submittedValue): static
    {
        $this->submittedValue = $submittedValue;

        return $this;
    }

    public function isRight(): ?bool
    {
        return $this->isRight;
    }

    public function setIsRight(bool $isRight): static
    {
        $this->isRight = $isRight;

        return $this;
    }

    public function getResolutionTime(): ?int
    {
        return $this->resolutionTime;
    }

    public function setResolutionTime(int $resolutionTime): static
    {
        $this->resolutionTime = $resolutionTime;

        return $this;
    }

    public function getAppliedPenalty(): ?int
    {
        return $this->appliedPenalty;
    }

    public function setAppliedPenalty(int $appliedPenalty): static
    {
        $this->appliedPenalty = $appliedPenalty;

        return $this;
    }

    public function getSubmissionDate(): ?\DateTimeImmutable
    {
        return $this->submissionDate;
    }

    public function setSubmissionDate(\DateTimeImmutable $submissionDate): static
    {
        $this->submissionDate = $submissionDate;

        return $this;
    }
}
