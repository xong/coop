<?php

namespace App\Form;

use App\Entity\Task;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class TaskFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Aufgabe',
                'constraints' => [new NotBlank()],
                'attr' => ['placeholder' => 'Was soll erledigt werden?'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Beschreibung (optional)',
                'required' => false,
                'attr' => ['rows' => 4],
            ])
            ->add('priority', ChoiceType::class, [
                'label' => 'Priorität',
                'choices' => [
                    'Niedrig'  => Task::PRIORITY_LOW,
                    'Mittel'   => Task::PRIORITY_MEDIUM,
                    'Hoch'     => Task::PRIORITY_HIGH,
                    'Dringend' => Task::PRIORITY_URGENT,
                ],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Offen'          => Task::STATUS_OPEN,
                    'In Bearbeitung' => Task::STATUS_IN_PROGRESS,
                    'Erledigt'       => Task::STATUS_DONE,
                    'Abgebrochen'    => Task::STATUS_CANCELLED,
                ],
            ])
            ->add('dueDate', DateType::class, [
                'label' => 'Fälligkeitsdatum (optional)',
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('assignees', EntityType::class, [
                'class' => User::class,
                'label' => 'Zugewiesen an (optional)',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'choices' => $options['members'],
                'choice_label' => 'fullName',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Task::class,
            'members' => [],
        ]);
    }
}
