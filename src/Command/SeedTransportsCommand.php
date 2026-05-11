<?php

namespace App\Command;

use App\Entity\Transport;
use App\Enum\TypeTransport;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SeedTransportsCommand extends Command
{
    protected static $defaultName = 'app:seed-transports';
    protected static $defaultDescription = 'Insère des données de transport de démonstration dans la base de données.';

    private ManagerRegistry $registry;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct();
        $this->registry = $registry;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $entityManager = $this->registry->getManager();

        $existing = $entityManager->getRepository(Transport::class)->count([]);
        if ($existing > 0) {
            $io->warning('La base contient déjà des transports. Aucun enregistrement n\'a été ajouté.');
            return Command::SUCCESS;
        }

        $samples = [
            [
                'type' => TypeTransport::AVION,
                'compagnie' => 'AirExpress',
                'origine' => 'Tunis',
                'destination' => 'Paris',
                'dateDepart' => new \DateTime('2026-05-20'),
                'heureDepart' => new \DateTime('09:30'),
                'dateArrivee' => new \DateTime('2026-05-20'),
                'heureArrivee' => new \DateTime('13:20'),
                'prix' => '520.00',
                'placesDisponibles' => 48,
                'numeroVol' => 'AE452',
                'description' => 'Vol direct Tunis-Paris avec service repas inclus.',
            ],
            [
                'type' => TypeTransport::TRAIN,
                'compagnie' => 'RailPlus',
                'origine' => 'Lyon',
                'destination' => 'Paris',
                'dateDepart' => new \DateTime('2026-05-18'),
                'heureDepart' => new \DateTime('07:15'),
                'dateArrivee' => new \DateTime('2026-05-18'),
                'heureArrivee' => new \DateTime('10:45'),
                'prix' => '120.00',
                'placesDisponibles' => 120,
                'numeroVol' => 'T987',
                'description' => 'Train rapide avec wifi gratuit et restauration à bord.',
            ],
            [
                'type' => TypeTransport::BUS,
                'compagnie' => 'CityBus',
                'origine' => 'Marseille',
                'destination' => 'Nice',
                'dateDepart' => new \DateTime('2026-05-21'),
                'heureDepart' => new \DateTime('08:00'),
                'dateArrivee' => new \DateTime('2026-05-21'),
                'heureArrivee' => new \DateTime('11:00'),
                'prix' => '45.00',
                'placesDisponibles' => 32,
                'numeroVol' => 'B124',
                'description' => 'Ligne express avec prise USB et climatisation.',
            ],
            [
                'type' => TypeTransport::BATEAU,
                'compagnie' => 'SeaVoyage',
                'origine' => 'Tunis',
                'destination' => 'Sicile',
                'dateDepart' => new \DateTime('2026-06-02'),
                'heureDepart' => new \DateTime('18:00'),
                'dateArrivee' => new \DateTime('2026-06-03'),
                'heureArrivee' => new \DateTime('09:00'),
                'prix' => '190.00',
                'placesDisponibles' => 85,
                'numeroVol' => 'SV210',
                'description' => 'Traversée confortable avec cabine et buffet.',
            ],
            [
                'type' => TypeTransport::TAXI,
                'compagnie' => 'GoTaxi',
                'origine' => 'Aéroport',
                'destination' => 'Centre-ville',
                'dateDepart' => new \DateTime('2026-05-15'),
                'heureDepart' => new \DateTime('14:30'),
                'prix' => '35.00',
                'placesDisponibles' => 4,
                'numeroVol' => 'TX01',
                'description' => 'Taxi privé avec prise en charge immédiate.',
            ],
            [
                'type' => TypeTransport::VOITURE,
                'compagnie' => 'DriveEasy',
                'origine' => 'Paris',
                'destination' => 'Lille',
                'dateDepart' => new \DateTime('2026-05-22'),
                'heureDepart' => new \DateTime('09:00'),
                'prix' => '220.00',
                'placesDisponibles' => 3,
                'numeroVol' => 'V100',
                'description' => 'Service premium en voiture avec chauffeur.',
            ],
        ];

        foreach ($samples as $sample) {
            $transport = new Transport();
            $transport->setType($sample['type']);
            $transport->setOrigine($sample['origine']);
            $transport->setDestination($sample['destination']);
            $transport->setDateDepart($sample['dateDepart']);
            $transport->setHeureDepart($sample['heureDepart']);
            $transport->setPrix($sample['prix']);
            $transport->setPlacesDisponibles($sample['placesDisponibles']);
            $transport->setCreatedAt(new \DateTimeImmutable());

            if (isset($sample['compagnie'])) {
                $transport->setCompagnie($sample['compagnie']);
            }
            if (isset($sample['dateArrivee'])) {
                $transport->setDateArrivee($sample['dateArrivee']);
            }
            if (isset($sample['heureArrivee'])) {
                $transport->setHeureArrivee($sample['heureArrivee']);
            }
            if (isset($sample['numeroVol'])) {
                $transport->setNumeroVol($sample['numeroVol']);
            }
            if (isset($sample['description'])) {
                $transport->setDescription($sample['description']);
            }
            if (isset($sample['categorie'])) {
                $transport->setCategorie($sample['categorie']);
            }

            $entityManager->persist($transport);
        }

        $entityManager->flush();

        $io->success('6 transports de démonstration ont été ajoutés à la base de données.');
        return Command::SUCCESS;
    }
}
