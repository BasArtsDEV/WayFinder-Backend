<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class InviteMemberType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('username', TextType::class, [
            'label' => 'Nom d\'utilisateur',
            'attr' => [
                'placeholder' => "Saisissez un nom d'utilisateur",
                'maxlength' => 30,
                'autocomplete' => 'off',
                'autofocus' => true,
            ],
            'constraints' => [
                new NotBlank(message: 'Veuillez saisir un nom d\'utilisateur.'),
                new Length(max: 30),
            ],
        ]);
    }
}
