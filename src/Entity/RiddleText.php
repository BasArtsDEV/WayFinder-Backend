<?php

namespace App\Entity;

use App\Repository\RiddleTextRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RiddleTextRepository::class)]
class RiddleText extends Riddle
{
    #[ORM\Column(length: 1024)]
    private string $question;

    #[ORM\Column(length: 1024)]
    private string $expectedAnswer;

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
