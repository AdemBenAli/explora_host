<?php

namespace App\Form;

use App\Entity\Transport;
use App\Enum\TypeTransport;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TransportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'choices' => TypeTransport::cases(),
                'choice_label' => fn(?TypeTransport $choice) => $choice ? $choice->getLabel() : '',
                'choice_value' => fn(?TypeTransport $choice) => $choice?->value,
                'label' => 'Type de transport',
            ])
            ->add('origine', TextType::class)
            ->add('destination', TextType::class)
            ->add('dateDepart', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('dateArrivee', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('heureDepart', TimeType::class, [
                'widget' => 'single_text',
            ])
            ->add('heureArrivee', TimeType::class, [
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('prix', MoneyType::class, [
                'currency' => 'TND',
                'scale' => 2,
            ])
            ->add('placesDisponibles')
            ->add('compagnie', TextType::class, [
                'required' => false,
            ])
            ->add('numeroVol', TextType::class, [
                'required' => false,
                'label' => 'N° Vol / Ticket',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Transport::class,
        ]);
    }
}
