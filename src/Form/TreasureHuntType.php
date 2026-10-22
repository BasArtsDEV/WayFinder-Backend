<?php

namespace App\Form;

use App\Entity\Team;
use App\Entity\TreasureHunt;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TreasureHuntType extends AbstractType
{
    public function __construct(
        private Security $security,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User|null $user */
        $user = $this->security->getUser();

        $builder
            ->add('title', TextType::class, [
                'attr' => [
                    'minlength' => 10,
                    'maxlength' => 120,
                    'placeholder' => 'Titre de la chasse au trésor (10 à 120 caractères)',
                ],
            ])
            ->add('description', TextareaType::class, [
                'attr' => [
                    'minlength' => 50,
                    'maxlength' => 1000,
                    'placeholder' => 'Titre de la chasse au trésor (50 à 1000 caractères)',
                ],
            ])
            ->add('team', EntityType::class, [
                'class' => Team::class,
                'choice_label' => 'name',
                'choices' => $user ? $user->getTeams()->filter(static fn (Team $team) => Team::ROLE_CREATOR === $team->getRole()) : [],
                'label' => 'Équipe',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TreasureHunt::class,
        ]);
    }
}
