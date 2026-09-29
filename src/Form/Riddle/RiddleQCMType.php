<?php

namespace App\Form\Riddle;

use App\Entity\RiddleQCM;
use App\Form\ChoiceItemType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RiddleQCMType extends RiddleType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('question', TextareaType::class, [
                'label' => 'Question posée',
            ])
            ->add('choices', CollectionType::class, [
                'entry_type' => ChoiceItemType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
                'label' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RiddleQCM::class,
        ]);
    }
}
