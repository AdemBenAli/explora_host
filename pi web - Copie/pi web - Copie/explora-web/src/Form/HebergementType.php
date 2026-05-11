<?php

namespace App\Form;

use App\Entity\Hebergement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class HebergementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Nom de l’hébergement',
                    'minlength' => 2,
                    'pattern' => '(?=(?:.*[A-Za-zÀ-ÿ]){2,}).{2,}',
                    'oninput' => "this.setCustomValidity('')",
                    'oninvalid' => "if(this.validity.valueMissing){this.setCustomValidity('Veuillez renseigner ce champ.');}else{this.setCustomValidity('Le nom de l’hébergement doit contenir au moins 2 lettres.');}",
                ],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'required' => true,
                'placeholder' => 'Choisir le type',
                'choices' => [
                    'Hotel' => 'Hotel',
                    'Hostel' => 'Hostel',
                    'Motel' => 'Motel',
                    'Maison' => 'Maison',
                    'Appartement' => 'Appartement',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Description...',
                    'rows' => 5,
                    'minlength' => 10,
                    'oninput' => "this.setCustomValidity('')",
                    'oninvalid' => "if(this.validity.valueMissing){this.setCustomValidity('Veuillez renseigner ce champ.');}else{this.setCustomValidity('La description doit contenir au moins 10 caractères.');}",
                ],
            ])
            ->add('prixParNuit', NumberType::class, [
                'label' => 'Prix par nuit',
                'required' => true,
                'html5' => true,
                'scale' => 2,
                'attr' => [
                    'placeholder' => 'Prix par nuit',
                    'min' => 0,
                    'step' => '0.01',
                    'oninput' => "this.setCustomValidity('')",
                    'oninvalid' => "if(this.validity.valueMissing){this.setCustomValidity('Veuillez renseigner ce champ.');}else{this.setCustomValidity('Le prix par nuit ne peut pas être négatif.');}",
                ],
            ])
            ->add('specialCouple', CheckboxType::class, [
                'label' => 'Spécial couple',
                'required' => false,
            ])
            ->add('under18Allowed', CheckboxType::class, [
                'label' => 'Moins de 18 ans autorisés',
                'required' => false,
            ])
            ->add('seaView', CheckboxType::class, [
                'label' => 'Vue sur mer',
                'required' => false,
            ])
            ->add('localisation', TextType::class, [
                'label' => 'Localisation',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ex: Tunis, La Marsa...',
                    'minlength' => 2,
                    'oninput' => "this.setCustomValidity('')",
                    'oninvalid' => "if(this.validity.valueMissing){this.setCustomValidity('Veuillez renseigner ce champ.');}else{this.setCustomValidity('La localisation doit contenir au moins 2 caractères.');}",
                ],
            ])
            ->add('latitude', NumberType::class, [
                'label' => 'Latitude',
                'required' => false,
                'html5' => true,
                'scale' => 6,
                'attr' => [
                    'placeholder' => 'Clique sur la map',
                ],
            ])
            ->add('longitude', NumberType::class, [
                'label' => 'Longitude',
                'required' => false,
                'html5' => true,
                'scale' => 6,
                'attr' => [
                    'placeholder' => 'Clique sur la map',
                ],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Photo',
                'required' => false,
                'mapped' => true,
                'attr' => [
                    'accept' => 'image/*',
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                            'image/jpg',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Veuillez choisir une image valide (JPG, PNG, WEBP, GIF).',
                    ]),
                ],
            ])
            ->add('capacite', NumberType::class, [
                'label' => 'Capacité',
                'required' => true,
                'html5' => true,
                'attr' => [
                    'placeholder' => 'Capacité',
                    'min' => 1,
                    'step' => 1,
                    'oninput' => "this.setCustomValidity('')",
                    'oninvalid' => "if(this.validity.valueMissing){this.setCustomValidity('Veuillez renseigner ce champ.');}else{this.setCustomValidity('La capacité doit être supérieure à 0.');}",
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Hebergement::class,
        ]);
    }
}