<?php

namespace App\Repository;

use App\Entity\Billet;
use App\Enum\StatutBillet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Billet>
 */
class BilletRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Billet::class);
    }

    /** Tous les billets avec transport chargé (évite N+1) */
    public function findAllWithTransport(): array
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.transport', 't')
            ->addSelect('t')
            ->orderBy('b.dateReservation', 'DESC')
            ->getQuery()->getResult();
    }

    /** Billets par statut */
    public function findByStatut(StatutBillet $statut): array
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.transport', 't')
            ->addSelect('t')
            ->where('b.statut = :statut')
            ->setParameter('statut', $statut)
            ->orderBy('b.dateReservation', 'DESC')
            ->getQuery()->getResult();
    }

    /** Billets d'un utilisateur */
    public function findByUserId(int $userId): array
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.transport', 't')
            ->addSelect('t')
            ->where('b.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('b.dateReservation', 'DESC')
            ->getQuery()->getResult();
    }

    /** Compte par statut */
    public function countByStatut(StatutBillet $statut): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->where('b.statut = :statut')
            ->setParameter('statut', $statut)
            ->getQuery()->getSingleScalarResult();
    }

    /** Revenu total des billets PAYÉS */
    public function calculerRevenuTotal(): float
    {
        return (float) $this->createQueryBuilder('b')
            ->select('COALESCE(SUM(b.prixTotal), 0)')
            ->where('b.statut = :statut')
            ->setParameter('statut', StatutBillet::PAYE)
            ->getQuery()->getSingleScalarResult();
    }

    /** Total billets */
    public function countTotal(): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->getQuery()->getSingleScalarResult();
    }
}