<?php

namespace App\Form;

use App\Entity\Team;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class TeamType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de l\'équipe',
                'attr' => [
                    'minlength' => 3,
                    'maxlength' => 30,
                    'placeholder' => 'Ex: Les Explorateurs',
                    'class' => 'form-control form-control-lg custom-input',
                ],
            ])
            ->add('bannerFile', FileType::class, [
                'label' => 'Bannière de l\'équipe',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File(maxSize: '2M', mimeTypes: [
                        'image/jpeg',
                        'image/png',
                        'image/webp',
                    ], mimeTypesMessage: 'Veuillez uploader une image valide (JPG, PNG, WEBP)'),
                ],
            ])
        ;

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $team = $event->getData();
            $file = $form->get('bannerFile')->getData();

            if ($file instanceof UploadedFile && $file->isValid()) {
                $binaryData = file_get_contents($file->getPathname());
                $mimeType = $file->getMimeType();
                $team->setBanner('data:'.$mimeType.';base64,'.base64_encode($binaryData));
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Team::class,
        ]);
    }
}
