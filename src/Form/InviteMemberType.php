<?php

namespace App\Form;

use App\Entity\Team;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class InviteMemberType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('username', UserAutoCompleteField::class, [
            'extra_options' => ['team_id' => $options['team']->getId()],
            'label' => 'Nom d\'utilisateur',
            'attr' => [
                'placeholder' => "Saisissez un nom d'utilisateur",
                'autocomplete' => 'new-password',
            ],
            'constraints' => [
                new NotBlank(message: 'Veuillez saisir un nom d\'utilisateur.'),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('team');
        $resolver->setAllowedTypes('team', Team::class);
    }
}
