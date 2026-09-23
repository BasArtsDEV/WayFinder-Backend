<?php

namespace App\Entity;

use App\Repository\RiddleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RiddleRepository::class)]
class Riddle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    private string $title;

    #[ORM\Column(type: Types::TEXT)]
    private string $description;

    #[ORM\Column(type: Types::BLOB, nullable: true)]
    private mixed $img = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $imgDescription = null;

    #[ORM\Column]
    private int $index;

    #[ORM\Column]
    private int $secondsPenalty = 0;

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
}
