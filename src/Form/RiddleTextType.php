<?php

namespace App\Form;

use App\Entity\RiddleText;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RiddleTextType extends RiddleType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('question', TextareaType::class, [
                'label' => 'Question posée',
            ])
            ->add('expectedAnswer', TextType::class, [
                'label' => 'Réponse attendue',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RiddleText::class,
        ]);
    }
}
