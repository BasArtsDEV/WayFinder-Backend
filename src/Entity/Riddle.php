<?php

namespace App\Entity;

use App\Repository\RiddleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\InheritanceType;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: RiddleRepository::class)]
#[InheritanceType('SINGLE_TABLE')]
#[DiscriminatorColumn(name: 'discr', type: 'string')]
#[DiscriminatorMap([
    'riddlegps' => RiddleGPS::class,
    'riddleqcm' => RiddleQCM::class,
    'riddleQRCode' => RiddleQRCode::class,
    'riddleText' => RiddleText::class,
])]
abstract class Riddle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['riddle:read', 'qcm:read', 'riddlegps:read', 'riddleQRCode:read', 'riddleText:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    #[Groups(['riddle:read', 'qcm:read', 'riddlegps:read', 'riddleQRCode:read', 'riddleText:read'])]
    private string $title;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['riddle:read', 'qcm:read', 'riddlegps:read', 'riddleQRCode:read', 'riddleText:read'])]
    private string $description;

    #[ORM\Column(type: Types::BLOB, nullable: true)]
    private mixed $img = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['riddle:read', 'qcm:read', 'riddlegps:read', 'riddleQRCode:read', 'riddleText:read'])]
    private ?string $imgDescription = null;

    #[ORM\Column]
    #[Groups(['riddle:read', 'qcm:read', 'riddlegps:read', 'riddleQRCode:read', 'riddleText:read'])]
    private int $index;

    #[ORM\Column]
    #[Groups(['riddle:read', 'qcm:read', 'riddlegps:read', 'riddleQRCode:read', 'riddleText:read'])]
    private int $secondsPenalty = 0;

    #[ORM\ManyToOne(inversedBy: 'riddles')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['riddle:read', 'qcm:read', 'riddlegps:read', 'riddleQRCode:read', 'riddleText:read'])]
    private TreasureHunt $treasureHunt;

    /**
     * @var Collection<int, Enrollement>
     */
    #[ORM\OneToMany(targetEntity: Enrollement::class, mappedBy: 'riddle')]
    private Collection $enrollements;

    /**
     * @var Collection<int, Submission>
     */
    #[ORM\OneToMany(targetEntity: Submission::class, mappedBy: 'riddle')]
    private Collection $submissions;

    #[ORM\ManyToOne(inversedBy: 'riddles')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['riddle:read', 'qcm:read', 'riddlegps:read', 'riddleQRCode:read', 'riddleText:read'])]
    private ?Location $location = null;

    public function __construct()
    {
        $this->enrollements = new ArrayCollection();
        $this->submissions = new ArrayCollection();
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

    public function getImg(): mixed
    {
        return $this->img;
    }

    public function setImg(mixed $img): static
    {
        $this->img = $img;

        return $this;
    }

    public function getImgDescription(): ?string
    {
        return $this->imgDescription;
    }

    public function setImgDescription(?string $imgDescription): static
    {
        $this->imgDescription = $imgDescription;

        return $this;
    }

    public function getIndex(): ?int
    {
        return $this->index;
    }

    public function setIndex(int $index): static
    {
        $this->index = $index;

        return $this;
    }

    public function getSecondsPenalty(): ?int
    {
        return $this->secondsPenalty;
    }

    public function setSecondsPenalty(int $secondsPenalty): static
    {
        $this->secondsPenalty = $secondsPenalty;

        return $this;
    }

    public function getTreasureHunt(): ?TreasureHunt
    {
        return $this->treasureHunt;
    }

    public function setTreasureHunt(?TreasureHunt $treasureHunt): static
    {
        $this->treasureHunt = $treasureHunt;

        return $this;
    }

    public function getEnrollements(): Collection
    {
        return $this->enrollements;
    }

    public function addEnrollement(Enrollement $enrollement): static
    {
        if (!$this->enrollements->contains($enrollement)) {
            $this->enrollements->add($enrollement);
            $enrollement->setRiddle($this);
        }

        return $this;
    }

    public function removeEnrollement(Enrollement $enrollement): static
    {
        if ($this->enrollements->removeElement($enrollement)) {
            if ($enrollement->getRiddle() === $this) {
                $enrollement->setRiddle(null);
            }
        }

        return $this;
    }

    public function getSubmissions(): Collection
    {
        return $this->submissions;
    }

    public function addSubmission(Submission $submission): static
    {
        if (!$this->submissions->contains($submission)) {
            $this->submissions->add($submission);
            $submission->setRiddle($this);
        }

        return $this;
    }

    public function removeSubmission(Submission $submission): static
    {
        if ($this->submissions->removeElement($submission)) {
            if ($submission->getRiddle() === $this) {
                $submission->setRiddle(null);
            }
        }

        return $this;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setLocation(?Location $location): static
    {
        $this->location = $location;

        return $this;
    }
}
