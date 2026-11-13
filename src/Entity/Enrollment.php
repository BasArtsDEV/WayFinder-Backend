<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\SortFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use App\Enum\EnrollmentStatus;
use App\Repository\EnrollmentRepository;
use App\State\EnrollmentProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EnrollmentRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_ENROLLMENT_USER_TREASURE_HUNT', fields: ['user', 'treasureHunt'])]
#[UniqueEntity(fields: ['user', 'treasureHunt'])]
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
                'groups' => ['enrollment:create'],
            ],
            security: "is_granted('ROLE_USER')",
            processor: EnrollmentProcessor::class,
        ),
        new Patch(
            normalizationContext: [
                'groups' => ['enrollment:read'],
            ],
            denormalizationContext: [
                'groups' => ['enrollment:write'],
            ],
            security: "is_granted('ROLE_USER') and object.getUser() == user",
            processor: EnrollmentProcessor::class,
        ),
        new Delete(
            security: "is_granted('ROLE_USER') and object.getUser() == user and object.getTeam() == null"
        ),
        new GetCollection(
            uriTemplate: '/treasure_hunts/{id}/enrollments',
            uriVariables: [
                'id' => new Link(toProperty: 'treasureHunt', fromClass: TreasureHunt::class),
            ],
            normalizationContext: [
                'groups' => ['feedback:read'],
            ],
            security: "is_granted('ROLE_USER')",
            parameters: [
                'hasFeedback' => new QueryParameter(
                    filter: new ExistsFilter(),
                    property: 'feedback',
                ),
                'sortByStars' => new QueryParameter(
                    filter: new SortFilter(),
                    property: 'nbStars',
                ),
            ]
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
            uriTemplate: '/teams/{id}/enrollments',
            uriVariables: [
                'id' => new Link(toProperty: 'team', fromClass: Team::class),
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
    #[Groups(['enrollment:read', 'feedback:read'])]
    private ?int $id = null;

    #[Groups(['enrollment:read', 'enrollment:write'])]
    #[ORM\Column(enumType: EnrollmentStatus::class)]
    private EnrollmentStatus $status = EnrollmentStatus::NOT_STARTED;

    #[ORM\Column]
    #[Groups(['enrollment:read'])]
    #[Assert\PositiveOrZero]
    private int $scoreTotal = 0;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Groups(['enrollment:read'])]
    private \DateTimeImmutable $registrationDate;

    #[ORM\Column]
    #[Groups(['enrollment:read'])]
    #[Assert\PositiveOrZero]
    private int $countdown = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['enrollment:read'])]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['enrollment:read'])]
    private ?\DateTimeImmutable $lastResumedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['enrollment:read'])]
    private ?\DateTimeImmutable $finishedAt = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $riddleElapsedTime = 0;

    #[ORM\ManyToOne(inversedBy: 'enrollments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['enrollment:read', 'enrollment:create'])]
    #[Assert\NotNull]
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
    #[Groups(['enrollment:read'])]
    #[ApiProperty(readableLink: false)]
    private ?Riddle $riddle = null;

    #[Groups(['enrollment:read', 'feedback:read'])]
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'enrollments')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Team::class, inversedBy: 'enrollments')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['enrollment:read', 'enrollment:create'])]
    private ?Team $team = null;

    #[Groups(['enrollment:read', 'enrollment:write', 'enrollment:details', 'feedback:read'])]
    #[ORM\Column(nullable: true)]
    private ?int $nbStars = null;

    #[Groups(['enrollment:read', 'enrollment:write', 'enrollment:details', 'feedback:read'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $feedback = null;

    public function __construct()
    {
        $this->submissions = new ArrayCollection();
        $this->registrationDate = new \DateTimeImmutable();
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

    public function getCountdown(?\DateTimeImmutable $now = null): int
    {
        if (EnrollmentStatus::IN_PROGRESS !== $this->status || null === $this->lastResumedAt) {
            return $this->countdown;
        }

        if (null === $now) {
            $now = new \DateTimeImmutable();
        }

        return $this->countdown + max(0, $now->getTimestamp() - $this->lastResumedAt->getTimestamp());
    }

    public function setCountdown(int $countdown): static
    {
        $this->countdown = $countdown;

        return $this;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getLastResumedAt(): ?\DateTimeImmutable
    {
        return $this->lastResumedAt;
    }

    public function setLastResumedAt(?\DateTimeImmutable $lastResumedAt): static
    {
        $this->lastResumedAt = $lastResumedAt;

        return $this;
    }

    public function getFinishedAt(): ?\DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?\DateTimeImmutable $finishedAt): static
    {
        $this->finishedAt = $finishedAt;

        return $this;
    }

    public function getRiddleElapsedTime(): int
    {
        return $this->riddleElapsedTime;
    }

    public function setRiddleElapsedTime(int $riddleElapsedTime): static
    {
        $this->riddleElapsedTime = $riddleElapsedTime;

        return $this;
    }

    public function getTreasureHunt(): ?TreasureHunt
    {
        return $this->treasureHunt ?? null;
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): static
    {
        $this->team = $team;

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
