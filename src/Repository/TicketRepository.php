<?php

namespace App\Repository;

use App\Entity\Ticket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ticket::class);
    }

    public function findAllOrderedByDate(): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatut(string $statut): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.statut = :statut')
            ->setParameter('statut', $statut)
            ->orderBy('t.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByPriorite(string $priorite): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.priorite = :priorite')
            ->setParameter('priorite', $priorite)
            ->orderBy('t.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByStatut(): array
    {
        $qb = $this->createQueryBuilder('t')
            ->select('t.statut, COUNT(t.id) as count')
            ->groupBy('t.statut');

        $results = $qb->getQuery()->getResult();
        $stats = [];

        foreach ($results as $result) {
            $stats[$result['statut']] = $result['count'];
        }

        $defaults = ['open' => 0, 'in_progress' => 0, 'resolved' => 0, 'closed' => 0];
        return array_merge($defaults, $stats);
    }

    /**
     * Compter les tickets par priorité
     */
    public function countByPriorite(): array
    {
        $qb = $this->createQueryBuilder('t')
            ->select('t.priorite, COUNT(t.id) as count')
            ->groupBy('t.priorite');

        $results = $qb->getQuery()->getResult();
        $stats = [];

        foreach ($results as $result) {
            $stats[$result['priorite']] = $result['count'];
        }

        $defaults = ['low' => 0, 'medium' => 0, 'high' => 0, 'urgent' => 0];
        return array_merge($defaults, $stats);
    }

    public function searchByKeyword(string $keyword): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.titre LIKE :keyword')
            ->orWhere('t.description LIKE :keyword')
            ->setParameter('keyword', '%' . $keyword . '%')
            ->orderBy('t.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
