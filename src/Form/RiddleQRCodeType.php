<?php

namespace App\Form;

use App\Entity\RiddleQRCode;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RiddleQRCodeType extends RiddleType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('url', UrlType::class, [
                'label' => 'URL du QR Code',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RiddleQRCode::class,
        ]);
    }
}
