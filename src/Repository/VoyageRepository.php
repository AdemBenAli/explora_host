<?php

namespace App\Repository;

use App\Entity\Voyage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Voyage>
 */
class VoyageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Voyage::class);
    }

    /** From group work - used by dashboard */
    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.idVoyage)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** From my work - used by VoyageController smart search & filter */
    public function searchAndFilter(?string $search, string $filter): array
    {
        $qb = $this->createQueryBuilder('v');

        if ($search !== null && $search !== '') {
            $qb->andWhere('LOWER(v.nom) LIKE LOWER(:search)')
               ->setParameter('search', '%' . $search . '%');
        }

        $today = new \DateTimeImmutable('today');

        if ($filter === 'en_cours') {
            $qb->andWhere('v.dateDepart <= :today')
               ->andWhere('v.dateRetour >= :today')
               ->setParameter('today', $today);
        } elseif ($filter === 'a_venir') {
            $qb->andWhere('v.dateDepart > :today')
               ->setParameter('today', $today);
        } elseif ($filter === 'termines') {
            $qb->andWhere('v.dateRetour < :today')
               ->setParameter('today', $today);
        }

        return $qb->orderBy('v.dateDepart', 'DESC')->getQuery()->getResult();
    }
}
