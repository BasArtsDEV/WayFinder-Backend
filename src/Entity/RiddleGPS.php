<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: \App\Repository\RiddleGPSRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['riddleGPS:read']],
            security: "is_granted('ROLE_USER')"
        ),

        new Get(
            normalizationContext: ['groups' => ['riddleGPS:read']],
            security: "is_granted('ROLE_USER')"
        ),
    ]
)]
class RiddleGPS extends Riddle
{
    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['riddleGPS:read'])]
    private ?string $locationName = null;

    #[ORM\Column]
    #[Groups(['riddleGPS:read'])]
    private float $latitude;

    #[ORM\Column]
    #[Groups(['riddleGPS:read'])]
    private float $longitude;

    public function getLocationName(): ?string
    {
        return $this->locationName;
    }

    public function setLocationName(?string $locationName): static
    {
        $this->locationName = $locationName;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(float $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(float $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }
}
