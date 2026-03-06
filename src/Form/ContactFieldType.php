<?php

namespace App\Form;

use App\Entity\ContactField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactFieldType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', TextType::class, [
                'attr' => ['placeholder' => 'Bezeichnung', 'class' => 'form-input'],
                'label' => false,
            ])
            ->add('value', TextType::class, [
                'attr' => ['placeholder' => 'Wert', 'class' => 'form-input'],
                'label' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ContactField::class]);
    }
}
