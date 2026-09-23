<?php

namespace App\Entity;

use App\Repository\RiddleTextRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RiddleTextRepository::class)]
class RiddleText
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 1024)]
    private string $question;

    #[ORM\Column(length: 1024)]
    private string $expectedAnswer;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuestion(): ?string
    {
        return $this->question;
    }

    public function setQuestion(string $question): static
    {
        $this->question = $question;

        return $this;
    }

    public function getExpectedAnswer(): ?string
    {
        return $this->expectedAnswer;
    }

    public function setExpectedAnswer(string $expectedAnswer): static
    {
        $this->expectedAnswer = $expectedAnswer;

        return $this;
    }
}
