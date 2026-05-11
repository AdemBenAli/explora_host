<?php

namespace App\Repository;

use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Utilisateur>
 */
class UtilisateurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utilisateur::class);
    }

    // You can add custom repository methods below:
    
    // public function findByEmail(string $email): ?Utilisateur
    // {
    //     return $this->createQueryBuilder('u')
    //         ->andWhere('u.email = :val')
    //         ->setParameter('val', $email)
    //         ->getQuery()
    //         ->getOneOrNullResult()
    //     ;
    // }

    public function findAgents(string $search = ''): array
{
    $qb = $this->createQueryBuilder('u')
        ->where('u.role = :role')
        ->setParameter('role', 'AGENT')
        ->orderBy('u.nom', 'ASC');

    if ($search !== '') {
        $qb->andWhere(
            $qb->expr()->orX(
                'LOWER(u.nom)    LIKE :q',
                'LOWER(u.prenom) LIKE :q',
                'LOWER(u.email)  LIKE :q',
            )
        )->setParameter('q', '%' . strtolower($search) . '%');
    }

    return $qb->getQuery()->getResult();
}

}
