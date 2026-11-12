<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use App\Repository\SubmissionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SubmissionRepository::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['submission:read', 'submission:details']]),
        new GetCollection(normalizationContext: ['groups' => ['submission:read', 'submission:details']]),

        new GetCollection(
            uriTemplate: '/enrollments/{id}/submissions',
            uriVariables: [
                'id' => new Link(toProperty: 'enrollment', fromClass: Enrollment::class),
            ],
            normalizationContext: ['groups' => ['submission:read', 'submission:details']],
        ),
        new GetCollection(
            uriTemplate: '/riddles/{id}/submissions',
            uriVariables: [
                'id' => new Link(toProperty: 'riddle', fromClass: Riddle::class),
            ],
            normalizationContext: ['groups' => ['submission:read', 'submission:details']],
        ),

        new Post(
            normalizationContext: ['groups' => ['submission:read', 'submission:details']],
            denormalizationContext: ['groups' => 'submission:write'],
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CREATOR')",
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CREATOR')",
        ),
    ]
)]
class Submission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['submission:read', 'submission:details'])]
    private ?int $id = null;

    #[ORM\Column(length: 2048)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 2048)]
    #[Groups(['submission:read', 'submission:details'])]
    private string $submittedValue = '';

    #[ORM\Column]
    #[Groups(['submission:read', 'submission:details'])]
    private bool $isRight = false;

    #[ORM\Column]
    #[Groups(['submission:read', 'submission:details'])]
    private int $resolutionTime = 0;

    #[ORM\Column]
    #[Groups(['submission:read', 'submission:details'])]
    private int $appliedPenalty = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['submission:read', 'submission:details'])]
    private \DateTimeImmutable $submissionDate;

    #[ORM\ManyToOne(inversedBy: 'submissions')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['submission:read', 'submission:details'])]
    private Enrollment $enrollment;

    #[ORM\ManyToOne(inversedBy: 'submissions')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['submission:read', 'submission:details'])]
    private Riddle $riddle;

    public function __construct()
    {
        $this->submissionDate = new \DateTimeImmutable();
    }

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

    public function getEnrollment(): ?Enrollment
    {
        return $this->enrollment ?? null;
    }

    public function setEnrollment(?Enrollment $enrollment): static
    {
        $this->enrollment = $enrollment;

        return $this;
    }

    public function getRiddle(): ?Riddle
    {
        return $this->riddle ?? null;
    }

    public function setRiddle(?Riddle $riddle): static
    {
        $this->riddle = $riddle;

        return $this;
    }
}
