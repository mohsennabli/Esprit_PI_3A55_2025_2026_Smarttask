<?php

namespace App\Repository;

use App\Entity\Commentaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commentaire>
 */
class CommentaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commentaire::class);
    }

    /**
     * Récupérer les commentaires d'un ticket
     */
    public function findByTicket(int $ticketId): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.ticket = :ticketId')
            ->setParameter('ticketId', $ticketId)
            ->orderBy('c.dateCommentaire', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compter les commentaires par ticket
     */
    public function countByTicket(int $ticketId): int
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.ticket = :ticketId')
            ->setParameter('ticketId', $ticketId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
