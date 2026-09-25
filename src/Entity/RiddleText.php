<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\RiddleTextRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: RiddleTextRepository::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['riddle_text:read', 'riddle_text:details']]),
        new GetCollection(normalizationContext: ['groups' => ['riddle_text:read', 'riddle_text:details']]),
    ]
)]
class RiddleText extends Riddle
{
    #[ORM\Column(length: 1024)]
    #[Groups(['riddle_text:read', 'riddle_text:details'])]
    private string $question;

    #[ORM\Column(length: 1024)]
    #[Groups(['riddle_text:read', 'riddle_text:details'])]
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
