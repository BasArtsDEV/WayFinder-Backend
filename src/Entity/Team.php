<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use App\Repository\TeamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TeamRepository::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['team:read', 'team:details']]),
        new GetCollection(normalizationContext: ['groups' => ['team:read', 'team:details']]),
        new GetCollection(
            uriTemplate: '/users/{id}/teams',
            uriVariables: [
                'id' => new Link(fromProperty: 'teams', fromClass: User::class),
            ],
            normalizationContext: ['groups' => ['team:read', 'team:details']],
        ),
        new Get(
            uriTemplate: '/treasure_hunts/{id}/team',
            uriVariables: [
                'id' => new Link(fromProperty: 'team', fromClass: TreasureHunt::class),
            ],
            normalizationContext: ['groups' => ['team:read', 'team:details']],
        ),
    ]
)]
class Team
{
    public const int MAX_USERS = 10;
    public const string ROLE_PARTICIPANT = 'participant';
    public const string ROLE_CREATOR = 'creator';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['team:read', 'team:details'])]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    #[Groups(['team:read', 'team:write', 'team:details', 'hunt:details'])]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 30)]
    private string $name;

    #[ORM\Column(length: 11)]
    #[Groups(['team:read', 'team:write', 'team:details'])]
    #[Assert\Choice(choices: [self::ROLE_PARTICIPANT, self::ROLE_CREATOR])]
    private string $role = self::ROLE_PARTICIPANT;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'ledTeams')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['team:read', 'team:write', 'team:details'])]
    private User $leader;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'teams')]
    #[Groups(['team:read', 'team:details'])]
    #[Assert\Count(min: 1)]
    #[Assert\Count(
        max: self::MAX_USERS,
        maxMessage: 'Une équipe ne peut pas contenir plus de '.self::MAX_USERS.' membres.'
    )]
    private Collection $users;

    /**
     * @var string|null
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private $banner;

    /**
     * @var Collection<int, Enrollment>
     */
    #[ORM\OneToMany(targetEntity: Enrollment::class, mappedBy: 'team')]
    private Collection $enrollments;

    /**
     * @var Collection<int, TreasureHunt>
     */
    #[ORM\OneToMany(
        targetEntity: TreasureHunt::class,
        mappedBy: 'team',
        cascade: ['remove'],
        orphanRemoval: true
    )]
    #[Groups(['team:read', 'team:details'])]
    private Collection $treasureHunts;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->enrollments = new ArrayCollection();
        $this->treasureHunts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getLeader(): ?User
    {
        return $this->leader ?? null;
    }

    public function setLeader(User $leader): static
    {
        $this->leader = $leader;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            if (count($this->users) >= self::MAX_USERS) {
                throw new \LogicException('Une équipe ne peut pas dépasser '.self::MAX_USERS.' membres.');
            }

            $this->users->add($user);
            $user->addTeam($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getTeams()->contains($this)) {
                $user->removeTeam($this);
            }
        }

        return $this;
    }

    public function getBanner(): ?string
    {
        return $this->banner;
    }

    public function setBanner(?string $banner): static
    {
        $this->banner = $banner;

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
            $enrollment->setTeam($this);
        }

        return $this;
    }

    public function removeEnrollment(Enrollment $enrollment): static
    {
        if ($this->enrollments->removeElement($enrollment)) {
            if ($enrollment->getTeam() === $this) {
                $enrollment->setTeam(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TreasureHunt>
     */
    public function getTreasureHunts(): Collection
    {
        return $this->treasureHunts;
    }

    public function addTreasureHunt(TreasureHunt $treasureHunt): static
    {
        if (!$this->treasureHunts->contains($treasureHunt)) {
            $this->treasureHunts->add($treasureHunt);
            $treasureHunt->setTeam($this);
        }

        return $this;
    }

    public function removeTreasureHunt(TreasureHunt $treasureHunt): static
    {
        $this->treasureHunts->removeElement($treasureHunt);

        return $this;
    }
}
