<?php

namespace App\Entity;

use App\Repository\EnrollementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EnrollementRepository::class)]
class Enrollement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 1)]
    private string $status;

    #[ORM\Column]
    private int $scoreTotal = 0;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $registrationDate;

    #[ORM\Column]
    private int $countdown;

    #[ORM\ManyToOne(inversedBy: 'enrollements')]
    #[ORM\JoinColumn(nullable: false)]
    private TreasureHunt $treasureHunt;

    /**
     * @var Collection<int, Submission>
     */
    #[ORM\OneToMany(targetEntity: Submission::class, mappedBy: 'enrollement')]
    private Collection $submissions;

    #[ORM\ManyToOne(inversedBy: 'enrollements')]
    private ?Riddle $riddle = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'enrollements')]
    private Collection $users;

    public function __construct()
    {
        $this->submissions = new ArrayCollection();
        $this->users = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getScoreTotal(): ?int
    {
        return $this->scoreTotal;
    }

    public function setScoreTotal(int $scoreTotal): static
    {
        $this->scoreTotal = $scoreTotal;

        return $this;
    }

    public function getRegistrationDate(): ?\DateTimeImmutable
    {
        return $this->registrationDate;
    }

    public function setRegistrationDate(\DateTimeImmutable $registrationDate): static
    {
        $this->registrationDate = $registrationDate;

        return $this;
    }

    public function getCountdown(): ?int
    {
        return $this->countdown;
    }

    public function setCountdown(int $countdown): static
    {
        $this->countdown = $countdown;

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
            $submission->setEnrollement($this);
        }

        return $this;
    }

    public function removeSubmission(Submission $submission): static
    {
        if ($this->submissions->removeElement($submission)) {
            // set the owning side to null (unless already changed)
            if ($submission->getEnrollement() === $this) {
                $submission->setEnrollement(null);
            }
        }

        return $this;
    }

    public function getRiddle(): ?Riddle
    {
        return $this->riddle;
    }

    public function setRiddle(?Riddle $riddle): static
    {
        $this->riddle = $riddle;

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
            $user->addEnrollement($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            $user->removeEnrollement($this);
        }

        return $this;
    }
}
