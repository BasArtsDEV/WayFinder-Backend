<?php

namespace App\Entity;

use App\Repository\LocationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LocationRepository::class)]
class Location
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(nullable: true)]
    private ?float $longitude = null;

    #[ORM\Column(type: Types::BLOB, nullable: true)]
    private mixed $img = null;

    /**
     * @var Collection<int, Riddle>
     */
    #[ORM\OneToMany(targetEntity: Riddle::class, mappedBy: 'location')]
    private Collection $riddles;

    public function __construct()
    {
        $this->riddles = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getImg(): mixed
    {
        return $this->img;
    }

    public function setImg(mixed $img): static
    {
        $this->img = $img;

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
            $riddle->setLocation($this);
        }

        return $this;
    }

    public function removeRiddle(Riddle $riddle): static
    {
        if ($this->riddles->removeElement($riddle)) {
            // set the owning side to null (unless already changed)
            if ($riddle->getLocation() === $this) {
                $riddle->setLocation(null);
            }
        }

        return $this;
    }
}
