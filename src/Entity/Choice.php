<?php

namespace App\Entity;

use App\Repository\ChoiceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChoiceRepository::class)]
class Choice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $choice;

    #[ORM\Column]
    private bool $isRight = false;

    #[ORM\ManyToOne(inversedBy: 'choices')]
    #[ORM\JoinColumn(nullable: false)]
    private RiddleQCM $riddleQCM;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChoice(): ?string
    {
        return $this->choice;
    }

    public function setChoice(string $choice): static
    {
        $this->choice = $choice;

        return $this;
    }

    public function isRight(): ?bool
    {
        return $this->isRight;
    }

    public function setIsRight(bool $isRight): static
    {
        $this->isRight = $isRight;

        return $this;
    }

    public function getRiddleQCM(): ?RiddleQCM
    {
        return $this->riddleQCM;
    }

    public function setRiddleQCM(?RiddleQCM $riddleQCM): static
    {
        $this->riddleQCM = $riddleQCM;

        return $this;
    }
}
