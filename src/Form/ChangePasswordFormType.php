<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ChangePasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'label' => 'Aktuelles Passwort',
                'mapped' => false,
                'constraints' => [new NotBlank()],
            ])
            ->add('newPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => ['label' => 'Neues Passwort'],
                'second_options' => ['label' => 'Neues Passwort wiederholen'],
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 8, 'minMessage' => 'Das Passwort muss mindestens 8 Zeichen haben.']),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
