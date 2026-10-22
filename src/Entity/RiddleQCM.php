<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use App\Repository\RiddleQCMRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RiddleQCMRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/riddle_qcms',
            normalizationContext: [
                'groups' => ['qcm:read'],
            ],
            security: 'true'
        ),

        new Get(
            uriTemplate: '/riddle_qcms/{id}',
            normalizationContext: [
                'groups' => ['qcm:read'],
            ],
            security: 'true'
        ),

        new Get(
            uriTemplate: '/choices/{id}/riddle_qcm',
            uriVariables: [
                'id' => new Link(fromProperty: 'riddleQCM', fromClass: Choice::class),
            ],
            normalizationContext: [
                'groups' => ['choice:read'],
            ],
            security: 'true'
        ),
    ]
)]
class RiddleQCM extends Riddle
{
    #[ORM\Column(length: 2048)]
    #[Groups(['qcm:read'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 2048)]
    private string $question;

    /**
     * @var Collection<int, Choice>
     */
    #[ORM\OneToMany(
        targetEntity: Choice::class,
        mappedBy: 'riddleQCM',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[Groups(['qcm:read'])]
    #[Assert\Count(
        max: 4,
        maxMessage: 'Vous ne pouvez pas ajouter plus de {{ limit }} choix de réponses.'
    )]
    private Collection $choices;

    public function __construct()
    {
        parent::__construct();

        $this->choices = new ArrayCollection();
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

    /**
     * @return Collection<int, Choice>
     */
    public function getChoices(): Collection
    {
        return $this->choices;
    }

    public function addChoice(Choice $choice): static
    {
        if (!$this->choices->contains($choice)) {
            $this->choices->add($choice);
            $choice->setRiddleQCM($this);
        }

        return $this;
    }

    public function removeChoice(Choice $choice): static
    {
        $this->choices->removeElement($choice);

        return $this;
    }
}
