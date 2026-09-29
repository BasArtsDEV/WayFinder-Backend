<?php

namespace App\Form\Riddle;

use App\Entity\RiddleGPS;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RiddleGPSType extends RiddleType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('latitude', NumberType::class, [
                'label' => 'Latitude',
                'scale' => 7,
            ])
            ->add('longitude', NumberType::class, [
                'label' => 'Longitude',
                'scale' => 7,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RiddleGPS::class,
        ]);
    }
}
