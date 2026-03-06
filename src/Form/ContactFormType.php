<?php

namespace App\Form;

use App\Entity\Contact;
use App\Entity\Project;
use App\Entity\Topic;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContactFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Vorname',
                'constraints' => [new NotBlank()],
                'attr' => ['placeholder' => 'Max'],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nachname',
                'constraints' => [new NotBlank()],
                'attr' => ['placeholder' => 'Mustermann'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'E-Mail (optional)',
                'required' => false,
                'attr' => ['placeholder' => 'max@beispiel.de'],
            ])
            ->add('phone', TextType::class, [
                'label' => 'Telefon (optional)',
                'required' => false,
                'attr' => ['placeholder' => '+49 123 456789'],
            ])
            ->add('company', TextType::class, [
                'label' => 'Unternehmen (optional)',
                'required' => false,
                'attr' => ['placeholder' => 'Musterfirma GmbH'],
            ])
            ->add('position', TextType::class, [
                'label' => 'Position (optional)',
                'required' => false,
                'attr' => ['placeholder' => 'Geschäftsführer'],
            ])
            ->add('website', UrlType::class, [
                'label' => 'Website (optional)',
                'required' => false,
                'default_protocol' => 'https',
                'attr' => ['placeholder' => 'https://beispiel.de'],
            ])
            ->add('address', TextareaType::class, [
                'label' => 'Adresse (optional)',
                'required' => false,
                'attr' => ['rows' => 3, 'placeholder' => 'Musterstraße 1\n12345 Musterstadt'],
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notizen (optional)',
                'required' => false,
                'attr' => ['rows' => 4],
            ])
            ->add('customFields', CollectionType::class, [
                'entry_type' => ContactFieldType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
            ])
            ->add('linkedProjects', EntityType::class, [
                'class' => Project::class,
                'label' => 'Verknüpfte Projekte (optional)',
                'multiple' => true,
                'required' => false,
                'choices' => $options['projects'],
                'choice_label' => 'name',
                'attr' => ['size' => 5],
            ])
            ->add('linkedTopics', EntityType::class, [
                'class' => Topic::class,
                'label' => 'Verknüpfte Themen (optional)',
                'multiple' => true,
                'required' => false,
                'choices' => $options['topics'],
                'choice_label' => 'title',
                'attr' => ['size' => 5],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contact::class,
            'projects'   => [],
            'topics'     => [],
        ]);
    }
}
