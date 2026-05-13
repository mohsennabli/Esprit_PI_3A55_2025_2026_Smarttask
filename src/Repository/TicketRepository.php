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

    public function filterTickets(?string $search, ?string $statut): array
    {
        $qb = $this->createQueryBuilder('t');

        if ($search) {
            $qb->andWhere('t.titre LIKE :search OR t.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($statut) {
            if ($statut === 'closed') {
                // Handle "closed" and any dirty data that the UI would fall back to "Fermé"
                // The UI considers anything not open, in_progress, or resolved as "Fermé"
                $qb->andWhere('t.statut NOT IN (:known_active)')
                   ->setParameter('known_active', ['open', 'in_progress', 'resolved']);
            } else {
                $qb->andWhere('t.statut = :statut')
                   ->setParameter('statut', $statut);
            }
        }

        return $qb->orderBy('t.dateCreation', 'DESC')->getQuery()->getResult();
    }

    public function countByStatut(): array
    {
        $qb = $this->createQueryBuilder('t')
            ->select('t.statut, COUNT(t.id) as count')
            ->groupBy('t.statut');

        $results = $qb->getQuery()->getResult();
        $stats = ['open' => 0, 'in_progress' => 0, 'resolved' => 0, 'closed' => 0];

        foreach ($results as $result) {
            $s = $result['statut'];
            if ($s === 'open') {
                $stats['open'] += $result['count'];
            } elseif ($s === 'in_progress') {
                $stats['in_progress'] += $result['count'];
            } elseif ($s === 'resolved') {
                $stats['resolved'] += $result['count'];
            } else {
                // Anything else falls into "Fermé" / "closed"
                $stats['closed'] += $result['count'];
            }
        }

        return $stats;
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
