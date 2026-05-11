<?php

namespace App\Repository;

use App\Entity\Avis;
use App\Entity\Hebergement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Avis>
 */
class AvisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Avis::class);
    }

    /**
     * @return Avis[]
     */
    public function findByHebergementOrdered(Hebergement $hebergement): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.hebergement = :hebergement')
            ->setParameter('hebergement', $hebergement)
            ->orderBy('a.dateAvis', 'DESC')
            ->addOrderBy('a.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getAverageForHebergement(Hebergement $hebergement): float
    {
        $qb = $this->createQueryBuilder('a')
            ->select('AVG(a.note) as avgNote')
            ->andWhere('a.hebergement = :hebergement')
            ->setParameter('hebergement', $hebergement)
            ->getQuery()
            ->getSingleScalarResult();

        return $qb !== null ? round((float) $qb, 1) : 0.0;
    }

    public function getCountForHebergement(Hebergement $hebergement): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.hebergement = :hebergement')
            ->setParameter('hebergement', $hebergement)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param int[] $hebergementIds
     * @return array<int, array{avg5: float, count: int, score10: float, starsRounded: int}>
     */
    public function getSummariesForHebergements(array $hebergementIds): array
    {
        $hebergementIds = array_values(array_filter(array_map('intval', $hebergementIds)));

        if ($hebergementIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('a')
            ->select('IDENTITY(a.hebergement) AS hebergementId')
            ->addSelect('AVG(a.note) AS avg5')
            ->addSelect('COUNT(a.id) AS countAvis')
            ->andWhere('a.hebergement IN (:ids)')
            ->setParameter('ids', $hebergementIds)
            ->groupBy('a.hebergement')
            ->getQuery()
            ->getArrayResult();

        $result = [];

        foreach ($hebergementIds as $id) {
            $result[$id] = [
                'avg5' => 0.0,
                'count' => 0,
                'score10' => 0.0,
                'starsRounded' => 0,
            ];
        }

        foreach ($rows as $row) {
            $hotelId = (int) ($row['hebergementId'] ?? 0);
            $avg5 = round((float) ($row['avg5'] ?? 0), 1);
            $count = (int) ($row['countAvis'] ?? 0);
            $score10 = round(($avg5 / 5) * 10, 1);
            $starsRounded = (int) round($avg5);

            $result[$hotelId] = [
                'avg5' => $avg5,
                'count' => $count,
                'score10' => $score10,
                'starsRounded' => $starsRounded,
            ];
        }

        return $result;
    }
}