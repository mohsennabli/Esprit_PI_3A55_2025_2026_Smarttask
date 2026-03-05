<?php

namespace App\Service;

use App\Entity\Projet;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailNotificationService
{
    private MailerInterface $mailer;
    private string $adminEmail;

    public function __construct(MailerInterface $mailer, string $adminEmail)
    {
        $this->mailer = $mailer;
        $this->adminEmail = $adminEmail;
    }

    public function notifyProjectUpdated(Projet $projet): void
    {
        $body = "Le projet '" . $projet->getNom() . "' a été modifié.\n\n"
            . "Détails :\n"
            . "- Nom : " . $projet->getNom() . "\n"
            . "- Description : " . ($projet->getDescription() ?: 'N/A') . "\n"
            . "- Statut : " . $projet->getStatut() . "\n"
            . "- Date de début : " . $projet->getDateDebut()->format('d/m/Y') . "\n"
            . "- Date d'échéance : " . $projet->getDateEcheance()->format('d/m/Y') . "\n\n"
            . "Notification envoyée le " . (new \DateTime())->format('d/m/Y à H:i:s');

        $email = (new Email())
            ->from('noreply@yourapp.com')
            ->to($this->adminEmail)
            ->subject('Projet mis à jour: ' . $projet->getNom())
            ->text($body);

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            throw new \Exception('Erreur envoi email: ' . $e->getMessage());
        }
    }

    public function notifyProjectDeleted(string $projectName): void
    {
        $body = "Le projet '$projectName' a été définitivement supprimé du système.\n\n"
            . "Date de suppression : " . (new \DateTime())->format('d/m/Y à H:i:s') . "\n\n"
            . "Toutes les tâches associées à ce projet ont également été supprimées.";

        $email = (new Email())
            ->from('noreply@yourapp.com')
            ->to($this->adminEmail)
            ->subject('Projet supprimé: ' . $projectName)
            ->text($body);

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            throw new \Exception('Erreur envoi email: ' . $e->getMessage());
        }
    }

}
