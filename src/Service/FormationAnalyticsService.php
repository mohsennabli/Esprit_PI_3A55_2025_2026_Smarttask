<?php

namespace App\Service;

use App\Entity\Formation;
use App\Repository\FormationRepository;
use App\Repository\InscriptionRepository;

/**
 * Analytics for the Formation/Inscription admin dashboard.
 */
class FormationAnalyticsService
{
    public function __construct(
        private readonly FormationRepository $formationRepository,
        private readonly InscriptionRepository $inscriptionRepository,
    ) {
    }

    public function getTotalFormations(): int
    {
        return (int) $this->formationRepository->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getTotalInscriptions(): int
    {
        return (int) $this->inscriptionRepository->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return Formation|null */
    public function getMostPopularFormation(): ?Formation
    {
        $result = $this->formationRepository->findMostPopular(1);
        return $result[0] ?? null;
    }

    /**
     * Completion rate: % of inscriptions with statut = completee.
     */
    public function getCompletionRate(): float
    {
        $total = $this->getTotalInscriptions();
        if ($total === 0) {
            return 0.0;
        }
        $completed = (int) $this->inscriptionRepository->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('i.statut = :statut')
            ->setParameter('statut', 'completee')
            ->getQuery()
            ->getSingleScalarResult();
        return round(100.0 * $completed / $total, 1);
    }

    /** @return Formation[] */
    public function getUpcomingFormationsThisWeek(): array
    {
        return $this->formationRepository->findUpcomingThisWeek();
    }
}
