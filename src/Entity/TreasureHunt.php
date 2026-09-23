<?php

namespace App\Entity;

use App\Repository\TreasureHuntRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TreasureHuntRepository::class)]
class TreasureHunt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    private string $title;

    #[ORM\Column(type: Types::TEXT)]
    private string $description;

    #[ORM\Column]
    private bool $isPublished;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $creationDate;

    #[ORM\ManyToOne(inversedBy: 'treasurehunts')]
    #[ORM\JoinColumn(nullable: false)]
    private Team $team;

    /**
     * @var Collection<int, Enrollement>
     */
    #[ORM\OneToMany(targetEntity: Enrollement::class, mappedBy: 'treasureHunt')]
    private Collection $enrollements;

    /**
     * @var Collection<int, Riddle>
     */
    #[ORM\OneToMany(targetEntity: Riddle::class, mappedBy: 'treasureHunt')]
    private Collection $riddles;

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
}
