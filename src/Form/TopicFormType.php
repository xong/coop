<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class TopicFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titel',
                'constraints' => [new NotBlank(), new Length(['max' => 255])],
                'attr' => ['placeholder' => 'Thema kurz beschreiben...'],
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Beitrag',
                'mapped' => false,
                'constraints' => [new NotBlank()],
                'attr' => ['rows' => 8, 'placeholder' => 'Ersten Beitrag schreiben...'],
            ])
            ->add('attachments', FileType::class, [
                'label' => 'Anhänge (optional)',
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'constraints' => [
                    new All([
                        new File(['maxSize' => '20M']),
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
