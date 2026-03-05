<?php

namespace App\Repository;

use App\Entity\Inscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class InscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Inscription::class);
    }

    /**
     * Inscriptions for formations starting in the given date range (for reminders).
     *
     * @return Inscription[]
     */
    public function findForReminder(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->createQueryBuilder('i')
            ->join('i.formation', 'f')
            ->addSelect('f')
            ->where('f.dateDebut BETWEEN :from AND :to')
            ->andWhere('f.statut = :active')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->setParameter('active', \App\Entity\Formation::STATUT_ACTIVE)
            ->getQuery()
            ->getResult();
    }

    /**
     * Inscriptions still "en_cours" for formations that have already ended (to mark absent).
     *
     * @return Inscription[]
     */
    public function findEnCoursForEndedFormations(): array
    {
        $today = new \DateTimeImmutable('today');
        return $this->createQueryBuilder('i')
            ->join('i.formation', 'f')
            ->addSelect('f')
            ->where('i.statut = :en_cours')
            ->andWhere('f.dateFin < :today')
            ->setParameter('en_cours', Inscription::STATUT_EN_COURS)
            ->setParameter('today', $today)
            ->getQuery()
            ->getResult();
    }

    /**
     * Inscriptions marked absent that have not yet received the follow-up email.
     *
     * @return Inscription[]
     */
    public function findAbsentWithoutFollowUpSent(): array
    {
        return $this->createQueryBuilder('i')
            ->join('i.formation', 'f')
            ->addSelect('f')
            ->where('i.statut = :absent')
            ->andWhere('i.absentFollowUpSentAt IS NULL')
            ->setParameter('absent', Inscription::STATUT_ABSENT)
            ->getQuery()
            ->getResult();
    }
}
