<?php

namespace App\Controller;

use App\Repository\TicketRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StatistiquesController extends AbstractController
{
    #[Route('/statistiques', name: 'statistiques_index')]
    public function index(TicketRepository $ticketRepository): Response
    {
        $stats = $ticketRepository->countByStatut();
        $priorites = $ticketRepository->countByPriorite();
        $total = array_sum($stats);

        $tauxResolution = $total > 0 ? round(($stats['resolved'] + $stats['closed']) / $total * 100, 2) : 0;

        return $this->render('statistiques/index.html.twig', [
            'stats' => $stats,
            'priorites' => $priorites,
            'total' => $total,
            'tauxResolution' => $tauxResolution
        ]);
    }

    #[Route('/api/stats', name: 'api_stats', methods: ['GET'])]
    public function apiStats(TicketRepository $ticketRepository): Response
    {
        $stats = $ticketRepository->countByStatut();
        $priorites = $ticketRepository->countByPriorite();
        $total = array_sum($stats);

        return $this->json([
            'total' => $total,
            'parStatut' => $stats,
            'parPriorite' => $priorites,
            'tauxResolution' => $total > 0 ? round(($stats['resolved'] + $stats['closed']) / $total * 100, 2) : 0
        ]);
    }
}
