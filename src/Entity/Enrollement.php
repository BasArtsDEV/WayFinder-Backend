<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Enum\EnrollementStatus;
use App\Repository\EnrollementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: EnrollementRepository::class)]
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/enrollements/{id}',
            normalizationContext: [
                'groups' => ['enrollement:read'],
            ],
            security: "is_granted('ROLE_USER')"
        ),
        new GetCollection(
            uriTemplate: '/enrollements',
            normalizationContext: [
                'groups' => ['enrollement:read'],
            ],
            security: "is_granted('ROLE_USER')"
        ),
        new Post(
            normalizationContext: [
                'groups' => ['enrollement:read'],
            ],
            denormalizationContext: [
                'groups' => ['enrollement:write'],
            ],
            security: "is_granted('ROLE_USER')"
        ),
        new Patch(
            normalizationContext: [
                'groups' => ['enrollement:read'],
            ],
            denormalizationContext: [
                'groups' => ['enrollement:write'],
            ],
            security: "is_granted('ROLE_USER')"
        ),
        new Delete(
            security: "is_granted('ROLE_USER')"
        ),
        new GetCollection(
            uriTemplate: '/treasure_hunts/{id}/enrollements',
            uriVariables: [
                'id' => new Link(toProperty: 'treasureHunt', fromClass: TreasureHunt::class),
            ],
            normalizationContext: [
                'groups' => ['user:read'],
            ],
            security: "is_granted('ROLE_USER')"
        ),
        new GetCollection(
            uriTemplate: '/users/{id}/enrollements',
            uriVariables: [
                'id' => new Link(toProperty: 'user', fromClass: User::class),
            ],
            normalizationContext: ['groups' => ['enrollement:read']],
            security: "is_granted('ROLE_USER')"
        ),
        new GetCollection(
            uriTemplate: '/riddles/{id}/enrollements',
            uriVariables: [
                'id' => new Link(toProperty: 'riddle', fromClass: Riddle::class),
            ],
            normalizationContext: ['groups' => ['enrollement:read']],
            security: "is_granted('ROLE_USER')"
        ),
        new Get(
            uriTemplate: '/submissions/{id}/enrollement',
            uriVariables: [
                'id' => new Link(fromProperty: 'enrollement', fromClass: Submission::class),
            ],
            normalizationContext: ['groups' => ['enrollement:read']],
            security: "is_granted('ROLE_USER')"
        ),
    ]
)]
class Enrollement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['enrollement:read'])]
    private ?int $id = null;

    #[Groups(['enrollement:read'])]
    #[ORM\Column(enumType: EnrollementStatus::class)]
    private EnrollementStatus $status;

    #[ORM\Column]
    #[Groups(['enrollement:read'])]
    private int $scoreTotal = 0;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Groups(['enrollement:read'])]
    private \DateTimeImmutable $registrationDate;

    #[ORM\Column]
    #[Groups(['enrollement:read'])]
    private int $countdown;

    #[ORM\ManyToOne(inversedBy: 'enrollements')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['enrollement:read', 'enrollement:write'])]
    private TreasureHunt $treasureHunt;

    /**
     * @var Collection<int, Submission>
     */
    #[ORM\OneToMany(
        targetEntity: Submission::class,
        mappedBy: 'enrollement',
        cascade: ['remove'],
        orphanRemoval: true
    )]
    #[Groups(['enrollement:read'])]
    private Collection $submissions;

    #[ORM\ManyToOne(inversedBy: 'enrollements')]
    #[Groups(['enrollement:read', 'enrollement:write'])]
    private ?Riddle $riddle = null;

    #[Groups(['enrollement:read'])]
    #[ORM\ManyToOne(inversedBy: 'enrollements')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    public function __construct()
    {
        $this->submissions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): EnrollementStatus
    {
        return $this->status;
    }

    public function setStatus(EnrollementStatus $status): static
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
            $submission->setEnrollement($this);
        }

        return $this;
    }

    public function removeSubmission(
        Submission $submission,
    ): static {
        if ($this->submissions->removeElement($submission)) {
            if ($submission->getEnrollement() === $this) {
                $submission->setEnrollement(null);
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
}
