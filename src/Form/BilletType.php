<?php

namespace App\Form;

use App\Entity\Billet;
use App\Entity\Transport;
use App\Enum\StatutBillet;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class BilletType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('transport', EntityType::class, [
                'class'        => Transport::class,
                'choice_label' => function (Transport $t): string {
                    return sprintf(
                        '%s %s | %s → %s (%s)',
                        $t->getType()->getIcon(),
                        $t->getCompagnie() ?? '',
                        $t->getOrigine(),
                        $t->getDestination(),
                        $t->getDateDepart()?->format('d/m/Y') ?? '-'
                    );
                },
                'label'       => 'Transport',
                'placeholder' => '-- Choisir un transport --',
                'attr'        => ['class' => 'form-select'],
                'constraints' => [
                    new Assert\NotNull(message: 'Le transport est obligatoire'),
                ],
            ])
            ->add('nombrePlaces', IntegerType::class, [
                'label'       => 'Nombre de places',
                'attr'        => [
                    'class'       => 'form-control',
                    'min'         => '1',
                    'max'         => '999',
                    'placeholder' => '1',
                ],
                'constraints' => [
                    new Assert\NotNull(message: 'Le nombre de places est obligatoire'),
                    new Assert\Positive(message: 'Au moins 1 place requise'),
                    new Assert\LessThanOrEqual(999, message: 'Maximum 999 places'),
                ],
            ])
            ->add('statut', EnumType::class, [
                'class'        => StatutBillet::class,
                'choice_label' => fn(StatutBillet $s) => $s->getLabel(),
                'label'        => 'Statut',
                'attr'         => ['class' => 'form-select'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Billet::class]);
    }
}