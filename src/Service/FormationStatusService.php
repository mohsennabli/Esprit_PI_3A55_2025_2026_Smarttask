<?php

namespace App\Service;

use App\Entity\Formation;
use App\Entity\Inscription;
use App\Repository\FormationRepository;
use App\Repository\InscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Handles automatic status updates for formations and inscriptions.
 * Designed to be run daily via cron (e.g. app:formation:status).
 */
class FormationStatusService
{
    public function __construct(
        private readonly FormationRepository $formationRepository,
        private readonly InscriptionRepository $inscriptionRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * Marks formations as completed when their end date has passed.
     *
     * @return int Number of formations updated
     */
    public function closeCompletedFormations(): int
    {
        $formations = $this->formationRepository->findEndedStillActive();
        foreach ($formations as $formation) {
            $formation->setStatut(Formation::STATUT_TERMINEE);
        }
        if (\count($formations) > 0) {
            $this->em->flush();
        }
        return \count($formations);
    }

    /**
     * Marks inscriptions as absent when the formation has ended and they were not completed.
     *
     * @return int Number of inscriptions updated
     */
    public function markAbsentInscriptions(): int
    {
        $inscriptions = $this->inscriptionRepository->findEnCoursForEndedFormations();
        foreach ($inscriptions as $inscription) {
            $inscription->setStatut(Inscription::STATUT_ABSENT);
        }
        if (\count($inscriptions) > 0) {
            $this->em->flush();
        }
        return \count($inscriptions);
    }

    /**
     * Runs all automatic status updates.
     *
     * @return array{formations_closed: int, inscriptions_marked_absent: int}
     */
    public function runAll(): array
    {
        $formationsClosed = $this->closeCompletedFormations();
        $inscriptionsMarkedAbsent = $this->markAbsentInscriptions();
        return [
            'formations_closed' => $formationsClosed,
            'inscriptions_marked_absent' => $inscriptionsMarkedAbsent,
        ];
    }
}
