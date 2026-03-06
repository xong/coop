<?php

namespace App\Form;

use App\Entity\CalendarEvent;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class CalendarEventFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titel',
                'constraints' => [new NotBlank()],
                'attr' => ['placeholder' => 'Name des Termins'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Beschreibung (optional)',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('location', TextType::class, [
                'label' => 'Ort (optional)',
                'required' => false,
                'attr' => ['placeholder' => 'Raum, Adresse oder Videolink'],
            ])
            ->add('startAt', DateTimeType::class, [
                'label' => 'Beginn',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'constraints' => [new NotBlank()],
            ])
            ->add('endAt', DateTimeType::class, [
                'label' => 'Ende (optional)',
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('allDay', CheckboxType::class, [
                'label' => 'Ganztägig',
                'required' => false,
            ])
            ->add('attendees', EntityType::class, [
                'class' => User::class,
                'label' => 'Teilnehmer (optional)',
                'multiple' => true,
                'required' => false,
                'choices' => $options['members'],
                'choice_label' => 'fullName',
                'attr' => ['size' => 5],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CalendarEvent::class,
            'members' => [],
        ]);
    }
}
