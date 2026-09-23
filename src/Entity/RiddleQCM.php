<?php

namespace App\Entity;

use App\Repository\RiddleQCMRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RiddleQCMRepository::class)]
class RiddleQCM extends Riddle
{
    #[ORM\Column(length: 2048)]
    private string $question;

    /**
     * @var Collection<int, Choice>
     */
    #[ORM\OneToMany(targetEntity: Choice::class, mappedBy: 'riddleQCM')]
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
        if ($this->choices->removeElement($choice)) {
            // set the owning side to null (unless already changed)
            if ($choice->getRiddleQCM() === $this) {
                $choice->setRiddleQCM(null);
            }
        }

        return $this;
    }
}
