<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use App\Repository\TreasureHuntRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Random\RandomException;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TreasureHuntRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['hunt:read', 'hunt:details']]),
        new GetCollection(normalizationContext: ['groups' => ['hunt:read', 'hunt:details']]),

        new Get(
            uriTemplate: '/riddles/{id}/treasure-hunt',
            uriVariables: [
                'id' => new Link(fromProperty: 'treasureHunt', fromClass: Riddle::class),
            ],
            normalizationContext: ['groups' => ['hunt:read', 'hunt:details']],
        ),
        new Get(
            uriTemplate: '/enrollments/{id}/treasure-hunt',
            uriVariables: [
                'id' => new Link(fromProperty: 'treasureHunt', fromClass: Enrollment::class),
            ],
            normalizationContext: ['groups' => ['hunt:read', 'hunt:details']],
        ),
        new GetCollection(
            uriTemplate: '/teams/{id}/treasure-hunts',
            uriVariables: [
                'id' => new Link(toProperty: 'team', fromClass: CreatorTeam::class),
            ],
            normalizationContext: ['groups' => ['hunt:read', 'hunt:details']],
        ),
    ]
)]
class TreasureHunt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['hunt:read', 'hunt:details'])]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    #[Groups(['hunt:read', 'hunt:write', 'hunt:details'])]
    #[Assert\NotBlank]
    private string $title;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['hunt:read', 'hunt:write', 'hunt:details'])]
    #[Assert\NotBlank]
    private string $description;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['hunt:read', 'hunt:write', 'hunt:details'])]
    private bool $published = false;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Groups(['hunt:read', 'hunt:details'])]
    private \DateTimeImmutable $creationDate;

    #[ORM\ManyToOne(targetEntity: CreatorTeam::class, inversedBy: 'treasureHunts')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['hunt:read', 'hunt:details'])]
    private CreatorTeam $team;

    /**
     * @var Collection<int, Enrollment>
     */
    #[ORM\OneToMany(targetEntity: Enrollment::class,
        mappedBy: 'treasureHunt',
        cascade: ['remove'],
        orphanRemoval: true
    )]
    #[Groups(['hunt:read', 'hunt:details'])]
    private Collection $enrollments;

    /**
     * @var Collection<int, Riddle>
     */
    #[ORM\OneToMany(targetEntity: Riddle::class,
        mappedBy: 'treasureHunt',
        cascade: ['remove'],
        orphanRemoval: true
    )]
    #[Groups(['hunt:read', 'hunt:write', 'hunt:details'])]
    private Collection $riddles;

    #[ORM\Column(length: 6, nullable: true)]
    #[Groups(['hunt:read', 'hunt:details'])]
    private ?string $invitationCode = null;

    public function __construct()
    {
        $this->enrollments = new ArrayCollection();
        $this->riddles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function isPublished(): bool
    {
        return $this->published;
    }

    public function setPublished(bool $published): static
    {
        $this->published = $published;

        return $this;
    }

    public function getCreationDate(): ?\DateTimeImmutable
    {
        return $this->creationDate;
    }

    public function setCreationDate(\DateTimeImmutable $creationDate): static
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getTeam(): ?CreatorTeam
    {
        return $this->team;
    }

    public function setTeam(?CreatorTeam $team): static
    {
        $this->team = $team;

        return $this;
    }

    /**
     * @return Collection<int, Enrollment>
     */
    public function getEnrollments(): Collection
    {
        return $this->enrollments;
    }

    public function addEnrollment(Enrollment $enrollment): static
    {
        if (!$this->enrollments->contains($enrollment)) {
            $this->enrollments->add($enrollment);
            $enrollment->setTreasureHunt($this);
        }

        return $this;
    }

    public function removeEnrollment(Enrollment $enrollment): static
    {
        if ($this->enrollments->removeElement($enrollment)) {
            // set the owning side to null (unless already changed)
            if ($enrollment->getTreasureHunt() === $this) {
                $enrollment->setTreasureHunt(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Riddle>
     */
    public function getRiddles(): Collection
    {
        return $this->riddles;
    }

    public function addRiddle(Riddle $riddle): static
    {
        if (!$this->riddles->contains($riddle)) {
            $this->riddles->add($riddle);
            $riddle->setTreasureHunt($this);
        }

        return $this;
    }

    public function removeRiddle(Riddle $riddle): static
    {
        if ($this->riddles->removeElement($riddle)) {
            // set the owning side to null (unless already changed)
            if ($riddle->getTreasureHunt() === $this) {
                $riddle->setTreasureHunt(null);
            }
        }

        return $this;
    }

    public function getInvitationCode(): ?string
    {
        return $this->invitationCode;
    }

    public function setInvitationCode(?string $invitationCode): static
    {
        $this->invitationCode = $invitationCode;

        return $this;
    }

    #[Groups(['hunt:read', 'hunt:details'])]
    public function getAverageStars(): ?float
    {
        $total = 0;
        $count = 0;

        foreach ($this->enrollments as $enrollment) {
            if (null !== $enrollment->getNbStars()) {
                $total += $enrollment->getNbStars();
                ++$count;
            }
        }

        return $count > 0 ? round($total / $count, 1) : null;
    }

    /**
     * @return string[]
     */
    public function getFeedbacks(): array
    {
        $feedbacks = [];
        foreach ($this->enrollments as $enrollment) {
            if (null !== $enrollment->getFeedback()) {
                $feedbacks[] = $enrollment->getFeedback();
            }
        }

        return $feedbacks;
    }

    /**
     * @throws RandomException
     */
    public function generateInvitationCode(): static
    {
        $bytes = random_bytes(3);
        $this->invitationCode = strtoupper(bin2hex($bytes));

        return $this;
    }

    #[ORM\PrePersist]
    public function setCreationDateValue(): void
    {
        if (!isset($this->creationDate)) {
            $this->creationDate = new \DateTimeImmutable();
        }
    }
}
