<?php

namespace App\Entity;

use App\Repository\RiddleQCMRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RiddleQCMRepository::class)]
class RiddleQCM
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 2048)]
    private string $question;

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
}
