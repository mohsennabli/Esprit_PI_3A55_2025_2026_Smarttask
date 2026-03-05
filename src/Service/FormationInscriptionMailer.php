<?php

namespace App\Service;

use App\Entity\Inscription;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;

class FormationInscriptionMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $fromEmail = 'no-reply@smarttask-manager.test'
    ) {
    }

    public function sendInscriptionConfirmation(Inscription $inscription): void
    {
        $user = $inscription->getUser();
        if (!$user || !$user->getEmail()) {
            return;
        }

        $formation = $inscription->getFormation();
        $titre = $formation?->getTitre() ?? '';
        $dateDebut = $formation?->getDateDebut()?->format('d/m/Y') ?? '';
        $dateFin = $formation?->getDateFin()?->format('d/m/Y') ?? '';
        $username = $user->getName() ?? $user->getEmail();

        $body = "Bonjour $username,\n\n"
            . "Votre inscription à la formation $titre a bien été enregistrée.\n\n"
            . "Date de début : $dateDebut\n"
            . "Date de fin : $dateFin\n"
            . "Statut : " . $inscription->getStatut() . "\n\n"
            . "Merci d'utiliser notre plateforme de gestion des tâches et formations.";

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($user->getEmail())
            ->subject("Confirmation d'inscription à la formation $titre")
            ->text($body);

        $this->mailer->send($email);
    }

    public function sendReminder3DaysBefore(Inscription $inscription): void
    {
        $user = $inscription->getUser();
        if (!$user || !$user->getEmail()) {
            return;
        }

        $formation = $inscription->getFormation();
        $titre = $formation?->getTitre() ?? '';
        $dateDebut = $formation?->getDateDebut()?->format('d/m/Y') ?? '';
        $dateFin = $formation?->getDateFin()?->format('d/m/Y') ?? '';
        $username = $user->getName() ?? $user->getEmail();

        $body = "Bonjour $username,\n\n"
            . "Rappel : votre formation $titre commence dans 3 jours.\n\n"
            . "Date de début : $dateDebut\n"
            . "Date de fin : $dateFin\n\n"
            . "Pensez à bloquer votre agenda. À très bientôt.";

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($user->getEmail())
            ->subject("Rappel : formation dans 3 jours – $titre")
            ->text($body);

        $this->mailer->send($email);
    }

    public function sendReminder24hBefore(Inscription $inscription): void
    {
        $user = $inscription->getUser();
        if (!$user || !$user->getEmail()) {
            return;
        }

        $formation = $inscription->getFormation();
        $titre = $formation?->getTitre() ?? '';
        $dateDebut = $formation?->getDateDebut()?->format('d/m/Y') ?? '';
        $dateFin = $formation?->getDateFin()?->format('d/m/Y') ?? '';
        $username = $user->getName() ?? $user->getEmail();

        $body = "Bonjour $username,\n\n"
            . "Ceci est un rappel : votre formation $titre commence demain.\n\n"
            . "Date de début : $dateDebut\n"
            . "Date de fin : $dateFin\n\n"
            . "Nous vous attendons. Excellente session.";

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($user->getEmail())
            ->subject("Rappel : votre formation commence demain")
            ->text($body);

        $this->mailer->send($email);
    }

    public function sendAbsentFollowUp(Inscription $inscription): void
    {
        $user = $inscription->getUser();
        if (!$user || !$user->getEmail()) {
            return;
        }

        $formation = $inscription->getFormation();
        $titre = $formation?->getTitre() ?? '';
        $dateDebut = $formation?->getDateDebut()?->format('d/m/Y') ?? '';
        $dateFin = $formation?->getDateFin()?->format('d/m/Y') ?? '';
        $username = $user->getName() ?? $user->getEmail();

        $body = "Bonjour $username,\n\n"
            . "Vous étiez inscrit(e) à la formation $titre ($dateDebut – $dateFin).\n"
            . "Nous n’avons pas pu constater votre participation.\n\n"
            . "Si vous souhaitez vous réinscrire à une prochaine session ou à une autre formation, n’hésitez pas à nous contacter.\n\n"
            . "Cordialement,\nL’équipe Formations";

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($user->getEmail())
            ->subject("Suivi : absence à la formation $titre")
            ->text($body);

        $this->mailer->send($email);
    }
}

