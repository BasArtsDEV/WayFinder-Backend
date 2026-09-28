<?php

namespace App\Form;

use App\Entity\Location;
use App\Entity\Riddle;
use App\Entity\TreasureHunt;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RiddleGPSType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('description')
            ->add('img')
            ->add('imgDescription')
            ->add('index')
            ->add('secondsPenalty')
            ->add('treasureHunt', EntityType::class, [
                'class' => TreasureHunt::class,
                'choice_label' => 'id',
            ])
            ->add('location', EntityType::class, [
                'class' => Location::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Riddle::class,
        ]);
    }
}
