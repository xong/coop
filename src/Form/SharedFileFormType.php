<?php

namespace App\Form;

use App\Entity\FileCategory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class SharedFileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('file', FileType::class, [
                'label' => 'Datei',
                'mapped' => false,
                'constraints' => [
                    new NotBlank(message: 'Bitte eine Datei auswählen.'),
                    new File([
                        'maxSize' => '50M',
                        'maxSizeMessage' => 'Die Datei darf maximal 50 MB groß sein.',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Beschreibung (optional)',
                'required' => false,
                'attr' => ['rows' => 3],
            ]);

        if (!empty($options['categories'])) {
            $builder->add('category', EntityType::class, [
                'class' => FileCategory::class,
                'label' => 'Kategorie (optional)',
                'required' => false,
                'placeholder' => '-- Keine Kategorie --',
                'choices' => $options['categories'],
                'choice_label' => function (FileCategory $cat) {
                    $prefix = $cat->getParent() ? '    ' : '';
                    return $prefix . $cat->getName();
                },
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'categories' => [],
        ]);
    }
}
