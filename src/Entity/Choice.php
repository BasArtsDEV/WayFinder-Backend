<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\ChoiceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ChoiceRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/choices',
            normalizationContext: [
                'groups' => ['choice:read'],
            ],
            security: 'true'
        ),

        new Get(
            uriTemplate: '/choices/{id}',
            normalizationContext: [
                'groups' => ['choice:read'],
            ],
            security: 'true'
        ),
    ]
)]
class Choice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['choice:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['choice:read'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $choice;

    #[ORM\Column]
    #[Groups(['choice:read'])]
    private bool $isRight = false;

    #[ORM\ManyToOne(
        targetEntity: RiddleQCM::class,
        inversedBy: 'choices'
    )]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['choice:read'])]
    private ?RiddleQCM $riddleQCM = null;

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

    public function isRight(): bool
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
