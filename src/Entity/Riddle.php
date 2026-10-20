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
use Symfony\Component\Validator\Constraints as Assert;

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
    #[Groups(['riddle:read', 'qcm:read', 'riddle_gps:read', 'riddle_qrcode:read', 'riddle_text:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    #[Groups(['riddle:read', 'qcm:read', 'riddle_gps:read', 'riddle_qrcode:read', 'riddle_text:read'])]
    private string $title;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['riddle:read', 'qcm:read', 'riddle_gps:read', 'riddle_qrcode:read', 'riddle_text:read'])]
    private string $description;

    #[ORM\Column(type: Types::BLOB, nullable: true)]
    private mixed $img = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['riddle:read', 'qcm:read', 'riddle_gps:read', 'riddle_qrcode:read', 'riddle_text:read'])]
    private ?string $imgDescription = null;

    #[ORM\Column]
    #[Groups(['riddle:read', 'qcm:read', 'riddle_gps:read', 'riddle_qrcode:read', 'riddle_text:read'])]
    private int $index;

    #[ORM\Column]
    #[Groups(['riddle:read', 'qcm:read', 'riddle_gps:read', 'riddle_qrcode:read', 'riddle_text:read'])]
    private int $secondsPenalty = 0;

    #[ORM\Column(options: ['default' => 0])]
    #[Assert\PositiveOrZero]
    private int $score = 0;

    #[ORM\ManyToOne(inversedBy: 'riddles')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['riddle:read', 'qcm:read', 'riddle_gps:read', 'riddle_qrcode:read', 'riddle_text:read'])]
    private TreasureHunt $treasureHunt;

    /**
     * @var Collection<int, Enrollment>
     */
    #[ORM\OneToMany(targetEntity: Enrollment::class, mappedBy: 'riddle')]
    private Collection $enrollments;

    /**
     * @var Collection<int, Submission>
     */
    #[ORM\OneToMany(targetEntity: Submission::class,
        mappedBy: 'riddle',
        cascade: ['remove'],
        orphanRemoval: true
    )]
    private Collection $submissions;

    #[ORM\ManyToOne(inversedBy: 'riddles')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['riddle:read', 'qcm:read', 'riddle_gps:read', 'riddle_qrcode:read', 'riddle_text:read'])]
    private ?Location $location = null;

    public function __construct()
    {
        $this->enrollments = new ArrayCollection();
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

    public function getImgBase64(): ?string
    {
        if (null === $this->img) {
            return null;
        }

        if (is_resource($this->img)) {
            return base64_encode(stream_get_contents($this->img));
        }

        return base64_encode($this->img);
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

    public function getScore(): int
    {
        return $this->score;
    }

    public function setScore(int $score): static
    {
        $this->score = $score;

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
            $enrollment->setRiddle($this);
        }

        return $this;
    }

    public function removeEnrollment(Enrollment $enrollment): static
    {
        if ($this->enrollments->removeElement($enrollment)) {
            if ($enrollment->getRiddle() === $this) {
                $enrollment->setRiddle(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Submission>
     */
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

    public function getTypeLabel(): string
    {
        return match (static::class) {
            RiddleQCM::class => 'QCM',
            RiddleGPS::class => 'GPS',
            RiddleQRCode::class => 'QR Code',
            RiddleText::class => 'Question simple',
            default => 'Autre',
        };
    }
}
