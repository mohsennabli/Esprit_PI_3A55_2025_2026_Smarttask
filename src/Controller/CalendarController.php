<?php

namespace App\Controller;

use App\Repository\TicketRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CalendarController extends AbstractController
{
    #[Route('/calendrier', name: 'calendar_index')]
    public function index(TicketRepository $ticketRepository): Response
    {
        // Récupérer tous les tickets
        $tickets = $ticketRepository->findAllOrderedByDate();

        // Transformer les tickets en tableau pour JavaScript
        $ticketsArray = [];
        foreach ($tickets as $ticket) {
            $ticketsArray[] = [
                'id' => $ticket->getId(),
                'titre' => $ticket->getTitre(),
                'description' => $ticket->getDescription(),
                'statut' => $ticket->getStatut(),
                'priorite' => $ticket->getPriorite(),
                'dateCreation' => $ticket->getDateCreation()->format('Y-m-d H:i:s')
            ];
        }

        return $this->render('calendar/index.html.twig', [
            'tickets' => $ticketsArray,
            'ticketsCount' => count($ticketsArray)
        ]);
    }
}
