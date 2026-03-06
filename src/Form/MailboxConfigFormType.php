<?php

namespace App\Form;

use App\Entity\MailboxConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class MailboxConfigFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $encryptionChoices = ['SSL' => 'ssl', 'TLS/STARTTLS' => 'tls', 'Keine' => 'none'];

        $builder
            ->add('name', TextType::class, [
                'label' => 'Bezeichnung',
                'constraints' => [new NotBlank()],
                'attr' => ['placeholder' => 'z.B. info@firma.de'],
            ])
            // IMAP
            ->add('imapHost', TextType::class, [
                'label' => 'IMAP-Server',
                'constraints' => [new NotBlank()],
                'attr' => ['placeholder' => 'imap.example.com'],
            ])
            ->add('imapPort', IntegerType::class, [
                'label' => 'IMAP-Port',
                'data' => 993,
            ])
            ->add('imapEncryption', ChoiceType::class, [
                'label' => 'IMAP-Verschlüsselung',
                'choices' => $encryptionChoices,
            ])
            ->add('imapUsername', TextType::class, [
                'label' => 'IMAP-Benutzername',
                'constraints' => [new NotBlank()],
                'attr' => ['placeholder' => 'user@example.com'],
            ])
            ->add('imapPassword', PasswordType::class, [
                'label' => 'IMAP-Passwort',
                'always_empty' => false,
                'attr' => ['autocomplete' => 'new-password'],
            ])
            ->add('imapFolder', TextType::class, [
                'label' => 'IMAP-Ordner',
                'data' => 'INBOX',
            ])
            // SMTP
            ->add('smtpHost', TextType::class, [
                'label' => 'SMTP-Server',
                'constraints' => [new NotBlank()],
                'attr' => ['placeholder' => 'smtp.example.com'],
            ])
            ->add('smtpPort', IntegerType::class, [
                'label' => 'SMTP-Port',
                'data' => 587,
            ])
            ->add('smtpEncryption', ChoiceType::class, [
                'label' => 'SMTP-Verschlüsselung',
                'choices' => $encryptionChoices,
            ])
            ->add('smtpUsername', TextType::class, [
                'label' => 'SMTP-Benutzername',
                'constraints' => [new NotBlank()],
                'attr' => ['placeholder' => 'user@example.com'],
            ])
            ->add('smtpPassword', PasswordType::class, [
                'label' => 'SMTP-Passwort',
                'always_empty' => false,
                'attr' => ['autocomplete' => 'new-password'],
            ])
            ->add('fromEmail', TextType::class, [
                'label' => 'Absender-E-Mail',
                'constraints' => [new NotBlank()],
                'attr' => ['placeholder' => 'info@example.com'],
            ])
            ->add('fromName', TextType::class, [
                'label' => 'Absender-Name',
                'constraints' => [new NotBlank()],
                'attr' => ['placeholder' => 'Meine Organisation'],
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Aktiv (automatisch synchronisieren)',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => MailboxConfig::class]);
    }
}
