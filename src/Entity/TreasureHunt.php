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
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TreasureHuntRepository::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['hunt:read', 'hunt:details']]),
        new GetCollection(normalizationContext: ['groups' => ['hunt:read', 'hunt:details']]),

        new Get(
            uriTemplate: '/riddles/{id}/treasure-hunt',
            uriVariables: [
                'id' => new Link(toProperty: 'riddles', fromClass: Riddle::class),
            ],
            normalizationContext: ['groups' => ['hunt:read', 'hunt:details']],
        ),
        new Get(
            uriTemplate: '/enrollements/{id}/treasure-hunt',
            uriVariables: [
                'id' => new Link(toProperty: 'enrollements', fromClass: Enrollement::class),
            ],
            normalizationContext: ['groups' => ['hunt:read', 'hunt:details']],
        ),
        new GetCollection(
            uriTemplate: '/teams/{id}/treasure-hunts',
            uriVariables: [
                'id' => new Link(toProperty: 'team', fromClass: Team::class),
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
    private bool $isPublished = false;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Groups(['hunt:read', 'hunt:details'])]
    private \DateTimeImmutable $creationDate;

    #[ORM\ManyToOne(inversedBy: 'treasurehunts')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['hunt:read', 'hunt:details'])]
    private Team $team;

    /**
     * @var Collection<int, Enrollement>
     */
    #[ORM\OneToMany(targetEntity: Enrollement::class, mappedBy: 'treasureHunt')]
    #[Groups(['hunt:read', 'hunt:details'])]
    private Collection $enrollements;

    /**
     * @var Collection<int, Riddle>
     */
    #[ORM\OneToMany(targetEntity: Riddle::class, mappedBy: 'treasureHunt')]
    #[Groups(['hunt:read', 'hunt:write', 'hunt:details'])]
    private Collection $riddles;

    #[ORM\Column(length: 6, nullable: true)]
    #[Groups(['hunt:read', 'hunt:details'])]
    private ?string $invitationCode = null;

    public function __construct()
    {
        $this->enrollements = new ArrayCollection();
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

    public function isPublished(): ?bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): static
    {
        $this->isPublished = $isPublished;

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

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): static
    {
        $this->team = $team;

        return $this;
    }

    /**
     * @return Collection<int, Enrollement>
     */
    public function getEnrollements(): Collection
    {
        return $this->enrollements;
    }

    public function addEnrollement(Enrollement $enrollement): static
    {
        if (!$this->enrollements->contains($enrollement)) {
            $this->enrollements->add($enrollement);
            $enrollement->setTreasureHunt($this);
        }

        return $this;
    }

    public function removeEnrollement(Enrollement $enrollement): static
    {
        if ($this->enrollements->removeElement($enrollement)) {
            // set the owning side to null (unless already changed)
            if ($enrollement->getTreasureHunt() === $this) {
                $enrollement->setTreasureHunt(null);
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
}
