<?php

namespace App\Controller;

use App\Service\FormationAnalyticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_MANAGER')]
#[Route('/formation')]
class FormationDashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_formation_dashboard', methods: ['GET'])]
    public function dashboard(FormationAnalyticsService $analytics): Response
    {
        return $this->render('front/formation/dashboard.html.twig', [
            'total_formations' => $analytics->getTotalFormations(),
            'total_inscriptions' => $analytics->getTotalInscriptions(),
            'most_popular_formation' => $analytics->getMostPopularFormation(),
            'completion_rate' => $analytics->getCompletionRate(),
            'upcoming_this_week' => $analytics->getUpcomingFormationsThisWeek(),
        ]);
    }
}
