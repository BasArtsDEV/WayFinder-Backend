<?php

namespace App\Entity;

use App\Repository\RiddleQCMRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RiddleQCMRepository::class)]
class RiddleQCM extends Riddle
{
    #[ORM\Column(length: 2048)]
    private string $question;

    public function getQuestion(): ?string
    {
        return $this->question;
    }

    public function setQuestion(string $question): static
    {
        $this->question = $question;

        return $this;
    }
}
