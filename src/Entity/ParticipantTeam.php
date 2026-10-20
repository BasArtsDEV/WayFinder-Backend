<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['team:read', 'team:details']]),
        new GetCollection(normalizationContext: ['groups' => ['team:read', 'team:details']]),

        new Get(
            uriTemplate: '/users/{id}/participant-teams',
            uriVariables: [
                'id' => new Link(fromProperty: 'teams', fromClass: User::class),
            ],
            normalizationContext: ['groups' => ['team:read', 'team:details']],
        ),
    ]
)]
class ParticipantTeam extends Team
{
    /**
     * @var Collection<int, Enrollment>
     */
    #[ORM\OneToMany(targetEntity: Enrollment::class, mappedBy: 'participantTeam')]
    private Collection $enrollments;

    public function __construct()
    {
        parent::__construct();
        $this->enrollments = new ArrayCollection();
    }

    /**
     * @return Collection<int, Enrollment>
     */
    public function getEnrollments(): Collection
    {
        return $this->enrollments;
    }

    public function addEnrollment(Enrollment $enrollment): static
    {
        if (!$this->enrollments->contains($enrollment)) {
            $this->enrollments->add($enrollment);
            $enrollment->setParticipantTeam($this);
        }

        return $this;
    }

    public function removeEnrollment(Enrollment $enrollment): static
    {
        if ($this->enrollments->removeElement($enrollment)) {
            if ($enrollment->getParticipantTeam() === $this) {
                $enrollment->setParticipantTeam(null);
            }
        }

        return $this;
    }
}
