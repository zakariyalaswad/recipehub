<?php

namespace App\Form;

use App\Entity\CategorieRecette;
use App\Entity\Recette;
use App\Entity\TagRecette;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;


class RecetteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
    ->add('titre')
    ->add('description')
    ->add('instructions')

    ->add('tempsPreparation')

    ->add('tempsCuisson')

    ->add('nbPersonnes')

    ->add('difficulte', ChoiceType::class, [
        'choices' => [
            'Facile' => 'facile',
            'Moyen' => 'moyen',
            'Difficile' => 'difficile',
        ]
    ])

    ->add('publiee', CheckboxType::class, [
        'required' => false
    ])

    ->add('categorie', EntityType::class, [
        'class' => CategorieRecette::class,
        'choice_label' => 'nom'
    ])

    ->add('tags', EntityType::class, [
        'class' => TagRecette::class,
        'choice_label' => 'nom',
        'multiple' => true,
        'expanded' => true,
        'by_reference' => false
    ])

    ->add('image', FileType::class, [
        'mapped' => false,
        'required' => false,
        'constraints' => [
            new File([
                'maxSize' => '2M',
                'mimeTypes' => [
                    'image/jpeg',
                    'image/png',
                    'image/webp'
                ]
            ])
        ]
    ])
;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Recette::class,
        ]);
    }
}
