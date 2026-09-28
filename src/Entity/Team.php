<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use App\Repository\TeamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TeamRepository::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['team:read', 'team:details']]),
        new GetCollection(normalizationContext: ['groups' => ['team:read', 'team:details']]),

        new Get(
            uriTemplate: '/users/{id}/team',
            uriVariables: [
                'id' => new Link(fromProperty: 'team', fromClass: User::class),
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
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['team:read', 'team:details'])]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    #[Groups(['team:read', 'team:write', 'team:details'])]
    #[Assert\NotBlank]
    private string $name;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'team')]
    #[Groups(['team:read', 'team:details'])]
    private Collection $users;

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
    private Collection $treasurehunts;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->treasurehunts = new ArrayCollection();
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

    /**
     * @return Collection<int, TreasureHunt>
     */
    public function gettreasurehunts(): Collection
    {
        return $this->treasurehunts;
    }

    public function addTreasureHunt(TreasureHunt $treasureHunt): static
    {
        if (!$this->treasurehunts->contains($treasureHunt)) {
            $this->treasurehunts->add($treasureHunt);
            $treasureHunt->setTeam($this);
        }

        return $this;
    }

    public function removeTreasureHunt(TreasureHunt $treasureHunt): static
    {
        if ($this->treasurehunts->removeElement($treasureHunt)) {
            if ($treasureHunt->getTeam() === $this) {
                $treasureHunt->setTeam(null);
            }
        }

        return $this;
    }
}
