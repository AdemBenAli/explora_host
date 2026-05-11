<?php

namespace App\Repository;

use App\Entity\Planning;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PlanningRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Planning::class);
    }

    public function findByVoyageur(int $idVoyageur): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.activite', 'a')
            ->where('p.idVoyageur = :id')
            ->setParameter('id', $idVoyageur)
            ->orderBy('p.date', 'ASC')
            ->addOrderBy('p.heureDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByVoyageurAndDate(int $idVoyageur, \DateTime $date): array
{
    return $this->createQueryBuilder('p')
        ->join('p.activite', 'a')
        ->where('p.idVoyageur = :v')
        ->andWhere('p.date = :d')
        ->setParameter('v', $idVoyageur)
        ->setParameter('d', $date->format('Y-m-d'))
        ->orderBy('p.heureDebut', 'ASC')
        ->getQuery()
        ->getResult();
}

    public function isAlreadyInPlanning(int $idVoyageur, int $idActivite): bool
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.idPlanning)')
            ->where('p.idVoyageur = :v AND p.activite = :a')
            ->setParameter('v', $idVoyageur)
            ->setParameter('a', $idActivite)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    public function hasConflict(
    int $idVoyageur,
    \DateTimeInterface $date,
    \DateTimeInterface $heureDebut,
    \DateTimeInterface $heureFin
): bool {
    // Convertir en string pour comparaison fiable avec le type TIME en base
    $debutStr = $heureDebut->format('H:i:s');
    $finStr   = $heureFin->format('H:i:s');
    $dateStr  = $date->format('Y-m-d');

    $conn = $this->getEntityManager()->getConnection();

    $sql = '
        SELECT COUNT(*)
        FROM planning p
        WHERE p.id_voyageur = :v
          AND p.date_activite = :date
          AND p.heure_debut IS NOT NULL
          AND p.heure_fin IS NOT NULL
          AND p.heure_debut < :fin
          AND p.heure_fin > :debut
    ';

    $count = $conn->fetchOne($sql, [
        'v'     => $idVoyageur,
        'date'  => $dateStr,
        'debut' => $debutStr,
        'fin'   => $finStr,
    ]);

    return (int) $count > 0;
}
}