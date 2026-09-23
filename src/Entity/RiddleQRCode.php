<?php

namespace App\Entity;

use App\Repository\RiddleQRCodeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RiddleQRCodeRepository::class)]
class RiddleQRCode extends Riddle
{
    #[ORM\Column(type: Types::STRING, length: 2048)]
    private string $url;

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }
}
