<?php

namespace App\Command;

use App\Entity\Transport;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:load-test-data',
    description: 'Load test data for Transport entity',
)]
class LoadTestDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Créer quelques transports de test
        $transports = [
            [
                'type' => 'AVION',
                'categorie' => 'ECONOMIQUE',
                'origine' => 'Tunis',
                'destination' => 'Paris',
                'dateDepart' => new \DateTime('2024-05-01'),
                'dateArrivee' => new \DateTime('2024-05-01'),
                'heureDepart' => new \DateTime('08:00:00'),
                'heureArrivee' => new \DateTime('10:30:00'),
                'prix' => '450.00',
                'placesDisponibles' => 150,
                'compagnie' => 'Tunisair',
                'numeroVol' => 'TU001',
                'description' => 'Vol direct Tunis-Paris',
                'etatTrafic' => 'FLUIDE',
                'createdAt' => new \DateTime(),
            ],
            [
                'type' => 'TRAIN',
                'categorie' => 'PREMIERE',
                'origine' => 'Tunis',
                'destination' => 'Sfax',
                'dateDepart' => new \DateTime('2024-05-02'),
                'heureDepart' => new \DateTime('06:00:00'),
                'heureArrivee' => new \DateTime('09:00:00'),
                'prix' => '25.00',
                'placesDisponibles' => 200,
                'compagnie' => 'SNCFT',
                'description' => 'Train grande ligne',
                'etatTrafic' => 'FLUIDE',
                'createdAt' => new \DateTime(),
            ],
            [
                'type' => 'BUS',
                'origine' => 'Tunis',
                'destination' => 'Sousse',
                'dateDepart' => new \DateTime('2024-05-03'),
                'heureDepart' => new \DateTime('07:00:00'),
                'heureArrivee' => new \DateTime('09:30:00'),
                'prix' => '12.00',
                'placesDisponibles' => 50,
                'compagnie' => 'SNTRI',
                'description' => 'Bus interurbain',
                'etatTrafic' => 'MODERE',
                'createdAt' => new \DateTime(),
            ],
        ];

        foreach ($transports as $data) {
            $transport = new Transport();
            foreach ($data as $key => $value) {
                $setter = 'set' . ucfirst($key);
                if (method_exists($transport, $setter)) {
                    $transport->$setter($value);
                }
            }
            $this->entityManager->persist($transport);
        }

        $this->entityManager->flush();

        $output->writeln('Données de test insérées avec succès!');

        return Command::SUCCESS;
    }
}