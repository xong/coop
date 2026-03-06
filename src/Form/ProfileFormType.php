<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Vorname',
                'constraints' => [new NotBlank(), new Length(['max' => 100])],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nachname',
                'constraints' => [new NotBlank(), new Length(['max' => 100])],
            ])
            ->add('email', EmailType::class, [
                'label' => 'E-Mail-Adresse',
            ])
            ->add('avatarFile', FileType::class, [
                'label' => 'Profilbild (optional)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Image([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'maxSizeMessage' => 'Das Bild darf maximal 2 MB groß sein.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class]);
    }
}
