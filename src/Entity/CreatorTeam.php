<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['team:read', 'team:details']]),
        new GetCollection(normalizationContext: ['groups' => ['team:read', 'team:details']]),

        new Get(
            uriTemplate: '/users/{id}/teams',
            uriVariables: [
                'id' => new Link(fromProperty: 'teams', fromClass: User::class),
            ],
            normalizationContext: ['groups' => ['team:read', 'team:details']],
        ),
        new Get(
            uriTemplate: '/treasure_hunts/{id}/team',
            uriVariables: [
                'id' => new Link(fromProperty: 'team', fromClass: TreasureHunt::class),
            ],
            normalizationContext: ['groups' => ['team:read', 'team:details']],
        ),
    ]
)]
class CreatorTeam extends Team
{
    /**
     * @var Collection<int, TreasureHunt>
     */
    #[ORM\OneToMany(
        targetEntity: TreasureHunt::class,
        mappedBy: 'team',
        cascade: ['remove'],
        orphanRemoval: true
    )]
    #[Groups(['team:read', 'team:details'])]
    private Collection $treasureHunts;

    public function __construct()
    {
        parent::__construct();
        $this->treasureHunts = new ArrayCollection();
    }

    /**
     * @return Collection<int, TreasureHunt>
     */
    public function getTreasureHunts(): Collection
    {
        return $this->treasureHunts;
    }

    public function addTreasureHunt(TreasureHunt $treasureHunt): static
    {
        if (!$this->treasureHunts->contains($treasureHunt)) {
            $this->treasureHunts->add($treasureHunt);
            $treasureHunt->setTeam($this);
        }

        return $this;
    }

    public function removeTreasureHunt(TreasureHunt $treasureHunt): static
    {
        $this->treasureHunts->removeElement($treasureHunt);

        return $this;
    }
}
