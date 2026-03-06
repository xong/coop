<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class TopicPostFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextareaType::class, [
                'label' => false,
                'constraints' => [new NotBlank(message: 'Der Beitrag darf nicht leer sein.')],
                'attr' => ['rows' => 5, 'placeholder' => 'Antwort schreiben...'],
            ])
            ->add('attachments', FileType::class, [
                'label' => 'Dateien anhängen (optional)',
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'constraints' => [
                    new All([new File(['maxSize' => '20M'])]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
