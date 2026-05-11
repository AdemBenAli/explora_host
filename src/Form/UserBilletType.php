<?php

namespace App\Form;

use App\Entity\Billet;
use App\Entity\Transport;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserBilletType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('transport', EntityType::class, [
                'class' => Transport::class,
                'choice_label' => function (Transport $transport) {
                    return sprintf('%s → %s (%s)', $transport->getOrigine(), $transport->getDestination(), $transport->getType());
                },
                'placeholder' => 'Choisir un transport',
            ])
            ->add('nombrePlaces', NumberType::class, [
                'scale' => 0,
                'attr' => ['min' => 1],
            ])
            ->add('prixTotal', NumberType::class, [
                'scale' => 2,
                'html5' => true,
                'attr' => ['step' => '0.01'],
            ])
            ->add('dateReservation', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('qrCode', TextareaType::class, [
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Billet::class,
        ]);
    }
}
