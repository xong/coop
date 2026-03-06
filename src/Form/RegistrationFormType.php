<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
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
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => ['label' => 'Passwort'],
                'second_options' => ['label' => 'Passwort wiederholen'],
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 8, 'minMessage' => 'Das Passwort muss mindestens 8 Zeichen haben.']),
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'label' => 'Ich akzeptiere die Nutzungsbedingungen.',
                'constraints' => [new IsTrue(message: 'Bitte akzeptiere die Nutzungsbedingungen.')],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class]);
    }
}
