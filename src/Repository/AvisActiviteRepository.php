<?php

namespace App\Repository;

use App\Entity\AvisActivite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AvisActiviteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AvisActivite::class);
    }

    /** Tous les avis d'une activité, du plus récent au plus ancien */
    public function findByActivite(int $idActivite): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.activite = :id')
            ->setParameter('id', $idActivite)
            ->orderBy('a.dateAvis', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** L'avis d'un voyageur précis pour une activité (null si aucun) */
    public function findMonAvis(int $idVoyageur, int $idActivite): ?AvisActivite
    {
        return $this->createQueryBuilder('a')
            ->where('a.idVoyageur = :v')
            ->andWhere('a.activite = :act')
            ->setParameter('v', $idVoyageur)
            ->setParameter('act', $idActivite)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** Moyenne des notes pour une activité */
    public function getMoyenne(int $idActivite): float
    {
        $result = $this->createQueryBuilder('a')
            ->select('AVG(a.note) as moy')
            ->where('a.activite = :id')
            ->setParameter('id', $idActivite)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? round((float) $result, 1) : 0.0;
    }

    /** Nombre d'avis pour une activité */
    public function getNbAvis(int $idActivite): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.idAvis)')
            ->where('a.activite = :id')
            ->setParameter('id', $idActivite)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** Total avis toutes activités confondues */
    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.idAvis)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** Retourne [idActivite => avgNote] pour toutes les activités ayant des avis */
    public function getAverageByActivite(): array
    {
        $rows = $this->createQueryBuilder('a')
            ->select('IDENTITY(a.activite) AS idActivite, AVG(a.note) AS moy')
            ->groupBy('a.activite')
            ->getQuery()
            ->getResult();

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['idActivite']] = round((float) $r['moy'], 2);
        }
        return $map;
    }

    /** Les $limit avis les plus récents pour une liste d'IDs d'activités */
    public function findRecentByActiviteIds(array $ids, int $limit = 5): array
    {
        if (empty($ids)) {
            return [];
        }

        return $this->createQueryBuilder('a')
            ->where('IDENTITY(a.activite) IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('a.idAvis', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /** Note moyenne pour toutes les activités d'un agent */
    public function getNoteMoyenneAgent(int $idAgent): float
    {
        $conn = $this->getEntityManager()->getConnection();
        $result = $conn->fetchOne(
            'SELECT AVG(av.note) FROM avis_activite av
             INNER JOIN activite a ON a.idActivite = av.idActivite
             WHERE a.id_agent = :agent',
            ['agent' => $idAgent]
        );
        return $result ? round((float) $result, 2) : 0.0;
    }

    /**
     * Top N agents classés par note moyenne des avis sur leurs activités.
     * Retourne : [['agentId', 'nomAgent', 'prenomAgent', 'noteMoyenne', 'nbAvis'], ...]
     */
    public function findTopAgentsBySatisfaction(int $limit = 5): array
    {
        return $this->getEntityManager()->createQuery(
            'SELECT u.id       AS agentId,
                    u.nom      AS nomAgent,
                    u.prenom   AS prenomAgent,
                    AVG(av.note) AS noteMoyenne,
                    COUNT(av.id) AS nbAvis
             FROM App\Entity\AvisActivite av
             JOIN av.activite a
             JOIN a.agent u
             GROUP BY u.id
             HAVING COUNT(av.id) > 0
             ORDER BY noteMoyenne DESC'
        )
        ->setMaxResults($limit)
        ->getArrayResult();
    }
 
    /**
     * Note moyenne globale de toutes les activités.
     */
    public function findGlobalAverageNote(): float
    {
        $result = $this->createQueryBuilder('av')
            ->select('AVG(av.note) AS moy')
            ->getQuery()
            ->getSingleScalarResult();
 
        return $result ? round((float) $result, 2) : 0.0;
    }
 
    /**
     * Les N avis les plus récents avec les infos liées.
     */
    public function findRecent(int $limit = 5): array
    {
        return $this->createQueryBuilder('av')
            ->select('av', 'a', 'u')
            ->join('av.activite', 'a')
            ->join('av.utilisateur', 'u')
            ->orderBy('av.dateAvis', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
 
    /**
     * Distribution des notes (1 à 5) : combien d'avis pour chaque note.
     * Retourne : [['note' => 5, 'nb' => 42], ...]
     */
    public function findNoteDistribution(): array
    {
        return $this->createQueryBuilder('av')
            ->select('av.note AS note, COUNT(av.id) AS nb')
            ->groupBy('av.note')
            ->orderBy('av.note', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

}