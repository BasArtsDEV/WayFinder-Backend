<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Regex;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'Nom d\'utilisateur',
                'label_attr' => [
                    'class' => 'fw-semibold',
                ],
                'attr' => [
                    'placeholder' => 'Entrez un nom d\'utilisateur',
                    'class' => 'rounded-pill border border-3 border-secondary mt-2 px-3 py-3',
                ],
            ])
            ->add('firstname', TextType::class, [
                'label' => 'Prénom',
                'label_attr' => [
                    'class' => 'fw-semibold',
                ],
                'attr' => [
                    'placeholder' => 'John',
                    'class' => 'rounded-pill border border-3 border-secondary mt-2 px-3 py-3',
                ],
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Nom',
                'label_attr' => [
                    'class' => 'fw-semibold',
                ],
                'attr' => [
                    'placeholder' => 'Doe',
                    'class' => 'rounded-pill border border-3 border-secondary mt-2 px-3 py-3',
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse mail',
                'label_attr' => [
                    'class' => 'fw-semibold',
                ],
                'attr' => [
                    'placeholder' => 'john.doe@gmail.com',
                    'class' => 'rounded-pill border border-3 border-secondary mt-2 px-3 py-3',
                ],
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Les mots de passe ne sont pas identiques',
                'options' => ['attr' => ['class' => 'password-field']],
                'required' => true,
                'first_options' => [
                    'label' => 'Mot de passe',
                    'label_attr' => [
                        'class' => 'fw-semibold',
                    ],
                    'attr' => [
                        'placeholder' => 'Saisir votre mot de passe',
                        'class' => 'rounded-pill border border-3 border-secondary mt-2 px-3 py-3',
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe',
                    'label_attr' => [
                        'class' => 'fw-semibold',
                    ],
                    'attr' => [
                        'placeholder' => 'Répéter votre mot de passe',
                        'class' => 'rounded-pill border border-3 border-secondary mt-2 px-3 py-3',
                    ],
                ],
                'constraints' => [
                    new Regex(
                        pattern: '/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/',
                        message: 'Le mot de passe doit contenir au moins 8 caractères, un chiffre, un symbole'
                    ),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
