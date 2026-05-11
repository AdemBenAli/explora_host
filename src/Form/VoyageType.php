<?php

namespace App\Form;

use App\Entity\Voyage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class VoyageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Titre',
                'constraints' => [
                    new Assert\NotBlank(message: 'Le titre est obligatoire.'),
                    new Assert\Length(max: 255),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'constraints' => [
                    new Assert\Length(max: 5000),
                ],
            ])
            ->add('dateDepart', DateType::class, [
                'label' => 'Date debut',
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\NotBlank(message: 'La date de debut est obligatoire.'),
                ],
            ])
            ->add('dateRetour', DateType::class, [
                'label' => 'Date fin',
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\NotBlank(message: 'La date de fin est obligatoire.'),
                ],
            ])
            ->add('budgetTotal', MoneyType::class, [
                'label' => 'Budget total',
                'currency' => 'EUR',
                'divisor' => 1,
                'constraints' => [
                    new Assert\NotBlank(message: 'Le budget est obligatoire.'),
                    new Assert\Positive(message: 'Le budget doit etre positif.'),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Voyage::class,
        ]);
    }
}
