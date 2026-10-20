<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;
use Symfony\Component\Validator\Constraints\Regex;

class ChangePasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'options' => [
                    'attr' => [
                        'class' => 'form-control form-control-lg rounded-3',
                    ],
                ],
                'first_options' => [
                    'label' => 'Nouveau mot de passe',
                    'label_attr' => ['class' => 'form-label-custom'],
                    'attr' => [
                        'placeholder' => 'Entrez votre nouveau mot de passe',
                        'autocomplete' => 'new-password',
                    ],
                    'constraints' => [
                        new NotBlank(
                            message: 'Veuillez renseigner un mot de passe.'
                        ),
                        new Regex(
                            pattern: '/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/',
                            message: 'Le mot de passe doit contenir au moins 8 caractères, une majuscule, un chiffre et un symbole.'
                        ),
                        new NotCompromisedPassword(
                            message: 'Ce mot de passe a été divulgué dans une fuite de données, veuillez en choisir un autre.'
                        ),
                    ],
                ],
                'second_options' => [
                    'label' => 'Répéter le mot de passe',
                    'label_attr' => ['class' => 'form-label-custom'],
                    'attr' => [
                        'placeholder' => 'Répétez votre nouveau mot de passe',
                        'autocomplete' => 'new-password',
                    ],
                ],
                'invalid_message' => 'Les mots de passe doivent correspondre.',
                'mapped' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
