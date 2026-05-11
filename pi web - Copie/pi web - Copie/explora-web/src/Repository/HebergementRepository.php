<?php

namespace App\Repository;

use App\Entity\Hebergement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Hebergement>
 */
class HebergementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Hebergement::class);
    }

    /**
     * @param array<string, mixed> $filters
     * @return Hebergement[]
     */
    public function findForFront(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('h');

        if (!empty($filters['destination'])) {
            $qb
                ->andWhere('LOWER(h.localisation) LIKE :destination OR LOWER(h.pays) LIKE :destination')
                ->setParameter('destination', '%'.mb_strtolower($filters['destination']).'%');
        }

        if (!empty($filters['type'])) {
            $qb
                ->andWhere('LOWER(h.type) = :type')
                ->setParameter('type', mb_strtolower($filters['type']));
        }

        if ($filters['minPrice'] !== null && $filters['minPrice'] !== '') {
            $qb
                ->andWhere('h.prixParNuit >= :minPrice')
                ->setParameter('minPrice', (float) $filters['minPrice']);
        }

        if ($filters['maxPrice'] !== null && $filters['maxPrice'] !== '') {
            $qb
                ->andWhere('h.prixParNuit <= :maxPrice')
                ->setParameter('maxPrice', (float) $filters['maxPrice']);
        }

        if (!empty($filters['specialCouple'])) {
            $qb->andWhere('h.specialCouple = :specialCouple')
               ->setParameter('specialCouple', true);
        }

        if (!empty($filters['under18Allowed'])) {
            $qb->andWhere('h.under18Allowed = :under18Allowed')
               ->setParameter('under18Allowed', true);
        }

        if (!empty($filters['seaView'])) {
            $qb->andWhere('h.seaView = :seaView')
               ->setParameter('seaView', true);
        }

        switch ($filters['sort'] ?? '') {
            case 'price_desc':
                $qb->orderBy('h.prixParNuit', 'DESC');
                break;

            case 'name_asc':
                $qb->orderBy('h.nom', 'ASC');
                break;

            case 'name_desc':
                $qb->orderBy('h.nom', 'DESC');
                break;

            case 'price_asc':
            default:
                $qb->orderBy('h.prixParNuit', 'ASC');
                break;
        }

        return $qb->getQuery()->getResult();
    }
}