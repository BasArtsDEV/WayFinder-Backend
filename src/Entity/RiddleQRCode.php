<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\RiddleQRCodeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: RiddleQRCodeRepository::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['riddle_qrcode:read', 'riddle_qrcode:details']]),
        new GetCollection(normalizationContext: ['groups' => ['riddle_qrcode:read', 'riddle_qrcode:details']]),
    ]
)]
class RiddleQRCode extends Riddle
{
    #[ORM\Column(type: Types::STRING, length: 2048)]
    #[Groups(['riddle_qrcode:read', 'riddle_qrcode:details'])]
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
