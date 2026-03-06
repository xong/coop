<?php

namespace App\Form;

use App\Entity\Project;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProjectFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Projektname',
                'constraints' => [new NotBlank(), new Length(['max' => 150])],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Beschreibung (optional)',
                'required' => false,
                'attr' => ['rows' => 4],
            ])
            ->add('isPublic', CheckboxType::class, [
                'label' => 'Offentliches Projekt (fur alle Organisationsmitglieder sichtbar)',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Project::class]);
    }
}
