<?php

namespace App\Repository;

use App\Entity\Activite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ActiviteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Activite::class);
    }

    /** Toutes les activités d'un agent avec filtres optionnels */
public function findByAgent(
    int    $idAgent,
    string $search        = '',
    string $categorie     = '',
    string $disponibilite = 'toutes'
): array {
    $qb = $this->createQueryBuilder('a')
        ->where('a.idAgent = :agent')
        ->setParameter('agent', $idAgent)
        ->orderBy('a.dateActivite', 'ASC');

    if ($search !== '') {
        $qb->andWhere(
            $qb->expr()->orX(
                'LOWER(a.nom)         LIKE :q',
                'LOWER(a.description) LIKE :q',
                'LOWER(a.ville)       LIKE :q',
                'LOWER(a.lieu)        LIKE :q',
            )
        )->setParameter('q', '%' . strtolower($search) . '%');
    }

    if ($categorie !== '') {
        $qb->andWhere('a.categorie = :cat')
           ->setParameter('cat', $categorie);
    }

    if ($disponibilite === 'disponibles') {
        $qb->andWhere('a.disponible = true');
    } elseif ($disponibilite === 'completes') {
        $qb->andWhere('a.disponible = false');
    }

    return $qb->getQuery()->getResult();
}

/** Catégories distinctes utilisées par un agent */
public function findCategoriesByAgent(int $idAgent): array
{
    return $this->createQueryBuilder('a')
        ->select('DISTINCT a.categorie')
        ->where('a.idAgent = :agent')
        ->setParameter('agent', $idAgent)
        ->getQuery()
        ->getSingleColumnResult();
}

    /** Toutes les activités avec filtres optionnels */
    public function findByFilters(
        string $search        = '',
        string $categorie     = '',
        string $disponibilite = 'toutes'
    ): array {
        $qb = $this->createQueryBuilder('a')
            ->orderBy('a.dateActivite', 'ASC');

        if ($search !== '') {
            $qb->andWhere(
                $qb->expr()->orX(
                    'LOWER(a.nom)         LIKE :q',
                    'LOWER(a.description) LIKE :q',
                    'LOWER(a.ville)       LIKE :q',
                    'LOWER(a.lieu)        LIKE :q'
                )
            )->setParameter('q', '%' . strtolower($search) . '%');
        }

        if ($categorie !== '') {
            $qb->andWhere('a.categorie = :cat')
               ->setParameter('cat', $categorie);
        }

        if ($disponibilite === 'disponibles') {
            $qb->andWhere('a.disponible = true');
        } elseif ($disponibilite === 'completes') {
            $qb->andWhere('a.disponible = false');
        }

        return $qb->getQuery()->getResult();
    }

    public function findByCategorie(string $categorie): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.categorie = :categorie')
            ->setParameter('categorie', $categorie)
            ->getQuery()
            ->getResult();
    }

    public function isNomUnique(string $nom, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('a')
            ->where('LOWER(a.nom) = LOWER(:nom)')
            ->setParameter('nom', $nom);

        if ($excludeId !== null) {
            $qb->andWhere('a.idActivite != :id')
               ->setParameter('id', $excludeId);
        }

        return count($qb->getQuery()->getResult()) === 0;
    }

    public function findDisponibles(): array
{
    return $this->createQueryBuilder('a')
        ->where('a.disponible = true')
        ->andWhere('a.nombrePlaces > 0')
        ->orderBy('a.dateActivite', 'ASC')
        ->addOrderBy('a.heureDebut', 'ASC')
        ->getQuery()
        ->getResult();
}

    /** All activities ordered by date */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.dateActivite', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** Distinct category values */
    public function findAllCategories(): array
    {
        return $this->createQueryBuilder('a')
            ->select('DISTINCT a.categorie')
            ->getQuery()
            ->getSingleColumnResult();
    }

    /** Raw SQL: returns [idActivite => voyageCount] sorted desc */
    public function findVoyageCountPerActivite(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $rows = $conn->fetchAllAssociative(
            'SELECT av.idActivite, COUNT(*) AS cnt
             FROM activite_voyage av
             GROUP BY av.idActivite
             ORDER BY cnt DESC'
        );
        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r['idActivite']] = (int)$r['cnt'];
        }
        return $map;
    }

    /** Find activités by agent filtered for dashboard (no extra filters) */
    public function findByAgentSimple(int $idAgent): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.idAgent = :agent')
            ->setParameter('agent', $idAgent)
            ->orderBy('a.dateActivite', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** Voyage count for activities belonging to given agent */
    public function findVoyageCountForAgent(int $idAgent): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $rows = $conn->fetchAllAssociative(
            'SELECT av.idActivite, COUNT(*) AS cnt
             FROM activite_voyage av
             INNER JOIN activite a ON a.idActivite = av.idActivite
             WHERE a.id_agent = :agent
             GROUP BY av.idActivite
             ORDER BY cnt DESC',
            ['agent' => $idAgent]
        );
        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r['idActivite']] = (int)$r['cnt'];
        }
        return $map;
    }

    // ─── Nouvelles méthodes statistiques ─────────────────────────────────────
 
    /**
     * Top N agents classés par nombre d'activités créées.
     * Retourne : [['agentId', 'nomAgent', 'prenomAgent', 'nbActivites'], ...]
     */
    public function findTopAgentsByActivityCount(int $limit = 5): array
    {
        return $this->createQueryBuilder('a')
            ->select(
                'u.id       AS agentId',
                'u.nom      AS nomAgent',
                'u.prenom   AS prenomAgent',
                'COUNT(a.id) AS nbActivites'
            )
            ->join('a.agent', 'u')
            ->groupBy('u.id')
            ->orderBy('nbActivites', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }
 
    /**
     * Top N activités classées par nombre de voyages associés.
     * Retourne : [['activiteId', 'nom', 'ville', 'categorie', 'nbVoyages'], ...]
     */
    public function findTopActivitesByVoyageCount(int $limit = 6): array
    {
        return $this->getEntityManager()->createQuery(
            'SELECT a.id AS activiteId,
                    a.nom,
                    a.ville,
                    a.categorie,
                    COUNT(v.id) AS nbVoyages
             FROM App\Entity\Activite a
             JOIN a.voyages v
             GROUP BY a.id
             ORDER BY nbVoyages DESC'
        )
        ->setMaxResults($limit)
        ->getArrayResult();
    }
 
    /**
     * Nombre d'activités par catégorie, triées par volume décroissant.
     * Retourne : [['categorie', 'nb'], ...]
     */
    public function countByCategorie(): array
    {
        return $this->createQueryBuilder('a')
            ->select('a.categorie AS categorie, COUNT(a.id) AS nb')
            ->where('a.categorie IS NOT NULL')
            ->groupBy('a.categorie')
            ->orderBy('nb', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }
 
    /**
     * Taux de disponibilité par catégorie.
     * Retourne : [['categorie', 'total', 'disponibles', 'tauxDispo'], ...]
     */
    public function findAvailabilityRateByCategorie(): array
    {
        $rows = $this->createQueryBuilder('a')
            ->select(
                'a.categorie AS categorie',
                'COUNT(a.id) AS total',
                'SUM(CASE WHEN a.disponible = true THEN 1 ELSE 0 END) AS disponibles'
            )
            ->where('a.categorie IS NOT NULL')
            ->groupBy('a.categorie')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getArrayResult();
 
        foreach ($rows as &$row) {
            $row['tauxDispo'] = $row['total'] > 0
                ? round($row['disponibles'] * 100 / $row['total'], 1)
                : 0;
        }
        unset($row);
 
        return $rows;
    }
 
    /**
     * Évolution mensuelle du nombre d'activités créées sur les N derniers mois.
     * Retourne : [['mois' => '2025-01', 'nb' => 12], ...]
     */
    public function findMonthlyCreationStats(int $months = 6): array
    {
        $since = new \DateTime("-{$months} months");
 
        $rows = $this->createQueryBuilder('a')
            ->select(
                "DATE_FORMAT(a.dateCreation, '%Y-%m') AS mois",
                'COUNT(a.id) AS nb'
            )
            ->where('a.dateCreation >= :since')
            ->setParameter('since', $since)
            ->groupBy('mois')
            ->orderBy('mois', 'ASC')
            ->getQuery()
            ->getArrayResult();
 
        return $rows;
    }
}