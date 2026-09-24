<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\LocationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: LocationRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/locations',
            normalizationContext: ['groups' => ['location:read']],
            security: "is_granted('ROLE_USER')"
        ),

        new Get(
            uriTemplate: '/locations/{id}',
            normalizationContext: ['groups' => ['location:read']],
            security: "is_granted('ROLE_USER')"
        ),
    ]
)]
class Location
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['location:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['location:read'])]
    private string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['location:read'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::BLOB, nullable: true)]
    #[Groups(['location:read'])]
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
            if ($riddle->getLocation() === $this) {
                $riddle->setLocation(null);
            }
        }

        return $this;
    }
}
