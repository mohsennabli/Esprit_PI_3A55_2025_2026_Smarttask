<?php

namespace App\Repository;

use App\Entity\Formation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FormationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Formation::class);
    }

    /** Formations whose end date is before today and still active (to be marked completed). */
    public function findEndedStillActive(): array
    {
        $today = new \DateTimeImmutable('today');
        return $this->createQueryBuilder('f')
            ->where('f.statut = :active')
            ->andWhere('f.dateFin < :today')
            ->setParameter('active', Formation::STATUT_ACTIVE)
            ->setParameter('today', $today)
            ->getQuery()
            ->getResult();
    }

    /** Formations starting within the given date range (for reminders). */
    public function findStartingBetween(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.dateDebut BETWEEN :from AND :to')
            ->andWhere('f.statut = :active')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->setParameter('active', Formation::STATUT_ACTIVE)
            ->getQuery()
            ->getResult();
    }

    /** Upcoming formations in the next 7 days (for dashboard). */
    public function findUpcomingThisWeek(): array
    {
        $today = new \DateTimeImmutable('today');
        $end = $today->modify('+7 days');
        return $this->createQueryBuilder('f')
            ->where('f.dateDebut >= :today')
            ->andWhere('f.dateDebut <= :end')
            ->andWhere('f.statut = :active')
            ->setParameter('today', $today)
            ->setParameter('end', $end)
            ->setParameter('active', Formation::STATUT_ACTIVE)
            ->orderBy('f.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** Most popular formation by inscription count (for dashboard). */
    public function findMostPopular(?int $limit = 1): array
    {
        $qb = $this->createQueryBuilder('f')
            ->select('f', 'COUNT(i.id) AS HIDDEN cnt')
            ->leftJoin('f.inscriptions', 'i')
            ->groupBy('f.id')
            ->orderBy('cnt', 'DESC')
            ->setMaxResults($limit);
        return $qb->getQuery()->getResult();
    }
}
