<?php

namespace App\Service;

use App\Entity\Inscription;
use App\Repository\FormationRepository;
use App\Repository\InscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Smart reminder system: 3 days before, 24h before, and follow-up for absent.
 * Orchestrates which inscriptions need which email and delegates sending to FormationInscriptionMailer.
 */
class FormationReminderService
{
    public function __construct(
        private readonly FormationRepository $formationRepository,
        private readonly InscriptionRepository $inscriptionRepository,
        private readonly FormationInscriptionMailer $mailer,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * Sends 3-day reminder to all inscriptions of formations starting in 3 days.
     *
     * @return int Number of emails sent
     */
    public function sendReminders3DaysBefore(): int
    {
        $today = new \DateTimeImmutable('today');
        $start = $today->modify('+3 days')->setTime(0, 0);
        $end = $today->modify('+3 days')->setTime(23, 59, 59);
        $inscriptions = $this->inscriptionRepository->findForReminder($start, $end);
        foreach ($inscriptions as $inscription) {
            $this->mailer->sendReminder3DaysBefore($inscription);
        }
        return \count($inscriptions);
    }

    /**
     * Sends 24h reminder to all inscriptions of formations starting tomorrow.
     *
     * @return int Number of emails sent
     */
    public function sendReminders24hBefore(): int
    {
        $today = new \DateTimeImmutable('today');
        $start = $today->modify('+1 day')->setTime(0, 0);
        $end = $today->modify('+1 day')->setTime(23, 59, 59);
        $inscriptions = $this->inscriptionRepository->findForReminder($start, $end);
        foreach ($inscriptions as $inscription) {
            $this->mailer->sendReminder24hBefore($inscription);
        }
        return \count($inscriptions);
    }

    /**
     * Sends follow-up email to inscriptions marked absent (not validated), then marks as sent.
     *
     * @return int Number of emails sent
     */
    public function sendAbsentFollowUps(): int
    {
        $inscriptions = $this->inscriptionRepository->findAbsentWithoutFollowUpSent();
        foreach ($inscriptions as $inscription) {
            $this->mailer->sendAbsentFollowUp($inscription);
            $inscription->setAbsentFollowUpSentAt(new \DateTime());
        }
        if (\count($inscriptions) > 0) {
            $this->em->flush();
        }
        return \count($inscriptions);
    }

    /**
     * Runs all reminder sends (3 days, 24h, absent follow-up).
     *
     * @return array{reminders_3d: int, reminders_24h: int, absent_follow_ups: int}
     */
    public function runAll(): array
    {
        return [
            'reminders_3d' => $this->sendReminders3DaysBefore(),
            'reminders_24h' => $this->sendReminders24hBefore(),
            'absent_follow_ups' => $this->sendAbsentFollowUps(),
        ];
    }
}
