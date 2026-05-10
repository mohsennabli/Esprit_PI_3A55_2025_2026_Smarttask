<?php

namespace App\Service;

use App\Repository\TicketRepository;

class ChatbotService
{
    private $ticketRepository;
    private $tickets = [];

    public function __construct(TicketRepository $ticketRepository)
    {
        $this->ticketRepository = $ticketRepository;
    }

    public function setTickets($tickets)
    {
        $this->tickets = $tickets;
    }

    public function processMessage(string $message): string
    {
        $msg = strtolower(trim($message));

        if (str_contains($msg, 'aide') || str_contains($msg, 'help')) {
            return $this->getHelp();
        }

        if (str_contains($msg, 'statistiques') || str_contains($msg, 'stat')) {
            return $this->getStatistics();
        }

        if (str_contains($msg, 'urgent')) {
            return $this->getUrgentTickets();
        }

        if (str_contains($msg, 'total') || str_contains($msg, 'combien')) {
            return $this->getTotalTickets();
        }

        if (str_contains($msg, 'ouvert')) {
            return $this->getOpenTickets();
        }

        if (str_contains($msg, 'en cours')) {
            return $this->getInProgressTickets();
        }

        if (str_contains($msg, 'resolu')) {
            return $this->getResolvedTickets();
        }

        if (str_contains($msg, 'liste')) {
            return $this->getTicketList();
        }

        if (str_contains($msg, 'bonjour') || str_contains($msg, 'salut')) {
            return "👋 Bonjour ! Comment puis-je vous aider ? Tapez 'aide' pour voir les commandes.";
        }

        if (str_contains($msg, 'merci')) {
            return "🙏 Je vous en prie !";
        }

        return $this->getDefaultResponse();
    }

    private function getHelp(): string
    {
        return "📋 **COMMANDES DISPONIBLES**\n\n" .
            "• **statistiques** - Voir les statistiques\n" .
            "• **tickets urgents** - Tickets urgents\n" .
            "• **total** - Nombre total de tickets\n" .
            "• **tickets ouverts** - Tickets ouverts\n" .
            "• **tickets en cours** - Tickets en progression\n" .
            "• **tickets résolus** - Tickets résolus\n" .
            "• **liste** - Afficher les tickets\n" .
            "• **aide** - Afficher cette aide";
    }

    private function getStatistics(): string
    {
        if (empty($this->tickets)) {
            return "📊 Aucun ticket disponible.";
        }

        $open = count(array_filter($this->tickets, fn($t) => $t->getStatut() == 'open'));
        $progress = count(array_filter($this->tickets, fn($t) => $t->getStatut() == 'in_progress'));
        $resolved = count(array_filter($this->tickets, fn($t) => $t->getStatut() == 'resolved'));
        $closed = count(array_filter($this->tickets, fn($t) => $t->getStatut() == 'closed'));

        return "📊 **STATISTIQUES**\n\n" .
            "🎫 Total : " . count($this->tickets) . " tickets\n" .
            "🟡 Ouvert : " . $open . "\n" .
            "🔄 En cours : " . $progress . "\n" .
            "✅ Résolu : " . $resolved . "\n" .
            "⚪ Fermé : " . $closed;
    }

    private function getTotalTickets(): string
    {
        return "📊 Nombre total de tickets : **" . count($this->tickets) . "**";
    }

    private function getUrgentTickets(): string
    {
        $urgent = count(array_filter($this->tickets, fn($t) => $t->getPriorite() == 'urgent'));
        return $urgent > 0 ? "⚠️ **" . $urgent . "** ticket(s) urgent(s) à traiter !" : "✅ Aucun ticket urgent !";
    }

    private function getOpenTickets(): string
    {
        $open = count(array_filter($this->tickets, fn($t) => $t->getStatut() == 'open'));
        return "📋 Tickets ouverts : **" . $open . "**";
    }

    private function getInProgressTickets(): string
    {
        $progress = count(array_filter($this->tickets, fn($t) => $t->getStatut() == 'in_progress'));
        return "🔄 Tickets en cours : **" . $progress . "**";
    }

    private function getResolvedTickets(): string
    {
        $resolved = count(array_filter($this->tickets, fn($t) => $t->getStatut() == 'resolved'));
        return "✅ Tickets résolus : **" . $resolved . "**";
    }

    private function getTicketList(): string
    {
        if (empty($this->tickets)) {
            return "📭 Aucun ticket à afficher.";
        }

        $result = "📋 **LISTE DES TICKETS**\n\n";
        $limit = min(5, count($this->tickets));
        for ($i = 0; $i < $limit; $i++) {
            $t = $this->tickets[$i];
            $result .= "   🎫 #" . $t->getId() . " : " . $t->getTitre() . "\n";
        }

        if (count($this->tickets) > 5) {
            $result .= "\n   ... et " . (count($this->tickets) - 5) . " autres tickets";
        }

        return $result;
    }

    private function getDefaultResponse(): string
    {
        return "🤖 Je n'ai pas compris votre demande.\n\nTapez **'aide'** pour voir les commandes disponibles.";
    }
}
