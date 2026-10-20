<?php

namespace App\Entity;

use App\Repository\TeamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TeamRepository::class)]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap(['creator' => CreatorTeam::class, 'participant' => ParticipantTeam::class])]
abstract class Team
{
    public const int MAX_USERS = 10;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['team:read', 'team:details'])]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    #[Groups(['team:read', 'team:write', 'team:details'])]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 30)]
    private string $name;

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

    public function __construct()
    {
        $this->users = new ArrayCollection();
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
}
