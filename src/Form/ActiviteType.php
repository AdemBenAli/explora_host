<?php
// src/Form/ActiviteType.php

namespace App\Form;

use App\Entity\Activite;
use App\Entity\Voyage;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ActiviteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // On récupère le mode (création ou modification) depuis les options
        $isEdit = $options['is_edit'];

        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de l\'activité',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom est obligatoire.']),
                    new Assert\Length([
                        'min'        => 3,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères.',
                    ]),
                ],
                'attr' => ['placeholder' => 'Ex: Plongée à Djerba'],
            ])

            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La description est obligatoire.']),
                    new Assert\Length([
                        'min'        => 3,
                        'minMessage' => 'La description doit contenir au moins {{ limit }} caractères.',
                    ]),
                ],
                'attr' => ['rows' => 4, 'placeholder' => 'Décrivez l\'activité...'],
            ])

            ->add('categorie', ChoiceType::class, [
                'label'   => 'Catégorie',
                'choices' => [
                    'Culture'        => 'CULTURE',
                    'Aventure'       => 'AVENTURE',
                    'Nature'         => 'NATURE',
                    'Détente'        => 'DETENTE',
                    'Gastronomie'    => 'GASTRONOMIE',
                    'Sport'          => 'SPORT',
                    'Shopping'       => 'SHOPPING',
                    'Divertissement' => 'DIVERTISSEMENT',
                    'Bien-être'      => 'BIEN_ETRE',
                    'Famille'        => 'FAMILLE',
                    'Romantique'     => 'ROMANTIQUE',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez sélectionner une catégorie.']),
                ],
            ])

            ->add('type', TextType::class, [
                'label'    => 'Type',
                'required' => false,
                'attr'     => ['placeholder' => 'Ex: Plein air'],
            ])

            ->add('ville', TextType::class, [
                'label' => 'Ville',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La ville est obligatoire.']),
                    new Assert\Length([
                        'min'        => 3,
                        'minMessage' => 'La ville doit contenir au moins {{ limit }} caractères.',
                    ]),
                ],
                'attr' => ['placeholder' => 'Ex: Tunis'],
            ])

            ->add('lieu', TextType::class, [
                'label' => 'Lieu',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le lieu est obligatoire.']),
                    new Assert\Length([
                        'min'        => 3,
                        'minMessage' => 'Le lieu doit contenir au moins {{ limit }} caractères.',
                    ]),
                ],
                'attr' => ['placeholder' => 'Ex: Plage de Hammamet'],
            ])

            ->add('prix', NumberType::class, [
                'label' => 'Prix (TND)',
                'scale' => 2,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le prix est obligatoire.']),
                    new Assert\Positive(['message' => 'Le prix doit être supérieur à 0.']),
                ],
                'attr' => ['placeholder' => 'Ex: 50.00', 'step' => '0.01'],
            ])

            ->add('duree', IntegerType::class, [
                'label' => 'Durée (minutes)',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La durée est obligatoire.']),
                    new Assert\Positive(['message' => 'La durée doit être supérieure à 0.']),
                ],
                'attr' => ['placeholder' => 'Ex: 120'],
            ])

            ->add('nombrePlaces', IntegerType::class, [
                'label' => 'Nombre de places',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nombre de places est obligatoire.']),
                    // En création : > 0 | En modification : >= 0
                    $isEdit
                        ? new Assert\PositiveOrZero(['message' => 'Le nombre de places ne peut pas être négatif.'])
                        : new Assert\Positive(['message' => 'Le nombre de places doit être supérieur à 0 lors de la création.']),
                ],
                'attr' => ['placeholder' => 'Ex: 20'],
            ])

            ->add('dateActivite', DateType::class, [
                'label'  => 'Date de l\'activité',
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez sélectionner une date.']),
                ],
            ])

            ->add('heureDebut', TimeType::class, [
                'label'  => 'Heure de début',
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'L\'heure de début est obligatoire.']),
                ],
            ])

            ->add('heureFin', TimeType::class, [
                'label'  => 'Heure de fin',
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'L\'heure de fin est obligatoire.']),
                ],
            ])

            ->add('imageFile', FileType::class, [
                'label'    => 'Image',
                'mapped'   => false,
                'required' => false,
                'constraints' => [
                    new Assert\File([
                        'mimeTypes'        => ['image/jpeg', 'image/png', 'image/gif'],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPG, PNG, GIF).',
                    ]),
                ],
            ])

            ->add('voyages', EntityType::class, [
                'class'        => Voyage::class,
                'choice_label' => fn(Voyage $v) => $v->getNom() . ' (' . $v->getVilleDepart() . ' → ' . $v->getVilleArrivee() . ')',
                'multiple'     => true,
                'expanded'     => false,
                'mapped'       => false,
                'required'     => false,
                'label'        => 'Voyages associés',
                'attr'         => ['size' => 5],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Activite::class,
            'is_edit'    => false,   // ← false = mode création par défaut
        ]);
    }
}