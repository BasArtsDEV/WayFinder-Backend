<?php

namespace App\Entity;

use App\Repository\RiddleGPSRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RiddleGPSRepository::class)]
class RiddleGPS extends Riddle
{
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $locationName = null;

    #[ORM\Column]
    private float $latitude;

    #[ORM\Column]
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
