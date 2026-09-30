<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Enum\EnrollmentStatus;
use App\Repository\EnrollmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: EnrollmentRepository::class)]
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/enrollments/{id}',
            normalizationContext: [
                'groups' => ['enrollment:read'],
            ],
            security: "is_granted('ROLE_USER')"
        ),
        new GetCollection(
            uriTemplate: '/enrollments',
            normalizationContext: [
                'groups' => ['enrollment:read'],
            ],
            security: "is_granted('ROLE_USER')"
        ),
        new Post(
            normalizationContext: [
                'groups' => ['enrollment:read'],
            ],
            denormalizationContext: [
                'groups' => ['enrollment:write'],
            ],
            security: "is_granted('ROLE_USER')"
        ),
        new Patch(
            normalizationContext: [
                'groups' => ['enrollment:read'],
            ],
            denormalizationContext: [
                'groups' => ['enrollment:write'],
            ],
            security: "is_granted('ROLE_USER')"
        ),
        new Delete(
            security: "is_granted('ROLE_USER')"
        ),
        new GetCollection(
            uriTemplate: '/treasure_hunts/{id}/enrollments',
            uriVariables: [
                'id' => new Link(toProperty: 'treasureHunt', fromClass: TreasureHunt::class),
            ],
            normalizationContext: [
                'groups' => ['user:read'],
            ],
            security: "is_granted('ROLE_USER')"
        ),
        new GetCollection(
            uriTemplate: '/users/{id}/enrollments',
            uriVariables: [
                'id' => new Link(toProperty: 'user', fromClass: User::class),
            ],
            normalizationContext: ['groups' => ['enrollment:read']],
            security: "is_granted('ROLE_USER')"
        ),
        new GetCollection(
            uriTemplate: '/riddles/{id}/enrollments',
            uriVariables: [
                'id' => new Link(toProperty: 'riddle', fromClass: Riddle::class),
            ],
            normalizationContext: ['groups' => ['enrollment:read']],
            security: "is_granted('ROLE_USER')"
        ),
        new Get(
            uriTemplate: '/submissions/{id}/enrollment',
            uriVariables: [
                'id' => new Link(fromProperty: 'enrollment', fromClass: Submission::class),
            ],
            normalizationContext: ['groups' => ['enrollment:read']],
            security: "is_granted('ROLE_USER')"
        ),
    ]
)]
class Enrollment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['enrollment:read'])]
    private ?int $id = null;

    #[Groups(['enrollment:read'])]
    #[ORM\Column(enumType: EnrollmentStatus::class)]
    private EnrollmentStatus $status;

    #[ORM\Column]
    #[Groups(['enrollment:read'])]
    private int $scoreTotal = 0;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Groups(['enrollment:read'])]
    private \DateTimeImmutable $registrationDate;

    #[ORM\Column]
    #[Groups(['enrollment:read'])]
    private int $countdown;

    #[ORM\ManyToOne(inversedBy: 'enrollments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['enrollment:read', 'enrollment:write'])]
    private TreasureHunt $treasureHunt;

    /**
     * @var Collection<int, Submission>
     */
    #[ORM\OneToMany(
        targetEntity: Submission::class,
        mappedBy: 'enrollment',
        cascade: ['remove'],
        orphanRemoval: true
    )]
    #[Groups(['enrollment:read'])]
    private Collection $submissions;

    #[ORM\ManyToOne(inversedBy: 'enrollments')]
    #[Groups(['enrollment:read', 'enrollment:write'])]
    private ?Riddle $riddle = null;

    #[Groups(['enrollment:read'])]
    #[ORM\ManyToOne(inversedBy: 'enrollments')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[Groups(['enrollment:read', 'enrollment:write', 'enrollment:details'])]
    #[ORM\Column(nullable: true)]
    private ?int $nbStars = null;

    #[Groups(['enrollment:read', 'enrollment:write', 'enrollment:details'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $feedback = null;

    public function __construct()
    {
        $this->submissions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): EnrollmentStatus
    {
        return $this->status;
    }

    public function setStatus(EnrollmentStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getScoreTotal(): ?int
    {
        return $this->scoreTotal;
    }

    public function setScoreTotal(int $scoreTotal): static
    {
        $this->scoreTotal = $scoreTotal;

        return $this;
    }

    public function getRegistrationDate(): ?\DateTimeImmutable
    {
        return $this->registrationDate;
    }

    public function setRegistrationDate(
        \DateTimeImmutable $registrationDate,
    ): static {
        $this->registrationDate = $registrationDate;

        return $this;
    }

    public function getCountdown(): ?int
    {
        return $this->countdown;
    }

    public function setCountdown(int $countdown): static
    {
        $this->countdown = $countdown;

        return $this;
    }

    public function getTreasureHunt(): ?TreasureHunt
    {
        return $this->treasureHunt;
    }

    public function setTreasureHunt(
        ?TreasureHunt $treasureHunt,
    ): static {
        $this->treasureHunt = $treasureHunt;

        return $this;
    }

    /**
     * @return Collection<int, Submission>
     */
    public function getSubmissions(): Collection
    {
        return $this->submissions;
    }

    public function addSubmission(
        Submission $submission,
    ): static {
        if (!$this->submissions->contains($submission)) {
            $this->submissions->add($submission);
            $submission->setEnrollment($this);
        }

        return $this;
    }

    public function removeSubmission(
        Submission $submission,
    ): static {
        if ($this->submissions->removeElement($submission)) {
            if ($submission->getEnrollment() === $this) {
                $submission->setEnrollment(null);
            }
        }

        return $this;
    }

    public function getRiddle(): ?Riddle
    {
        return $this->riddle;
    }

    public function setRiddle(?Riddle $riddle): static
    {
        $this->riddle = $riddle;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getNbStars(): ?int
    {
        return $this->nbStars;
    }

    public function setNbStars(?int $nbStars): static
    {
        $this->nbStars = $nbStars;

        return $this;
    }

    public function getFeedback(): ?string
    {
        return $this->feedback;
    }

    public function setFeedback(?string $feedback): static
    {
        $this->feedback = $feedback;

        return $this;
    }
}
