<?php

namespace App\Entity;

use App\Repository\TeamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeamRepository::class)]
class Team
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    private string $name;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'team')]
    private Collection $users;

    /**
     * @var Collection<int, TreasureHunt>
     */
    #[ORM\OneToMany(targetEntity: TreasureHunt::class, mappedBy: 'team')]
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
            $user->setTeam($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getTeam() === $this) {
                $user->setTeam(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TreasureHunt>
     */
    public function getTreasurehunts(): Collection
    {
        return $this->treasurehunts;
    }

    public function addTreasurehunt(TreasureHunt $treasurehunt): static
    {
        if (!$this->treasurehunts->contains($treasurehunt)) {
            $this->treasurehunts->add($treasurehunt);
            $treasurehunt->setTeam($this);
        }

        return $this;
    }

    public function removeTreasurehunt(TreasureHunt $treasurehunt): static
    {
        if ($this->treasurehunts->removeElement($treasurehunt)) {
            // set the owning side to null (unless already changed)
            if ($treasurehunt->getTeam() === $this) {
                $treasurehunt->setTeam(null);
            }
        }

        return $this;
    }
}
