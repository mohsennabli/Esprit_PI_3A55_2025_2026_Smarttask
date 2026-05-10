<?php

namespace App\Controller;

use App\Repository\TicketRepository;
use App\Service\ChatbotService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChatbotController extends AbstractController
{
    #[Route('/chatbot', name: 'chatbot_index')]
    public function index(TicketRepository $ticketRepository): Response
    {
        $tickets = $ticketRepository->findAllOrderedByDate();

        return $this->render('chatbot/index.html.twig', [
            'tickets' => $tickets
        ]);
    }

    #[Route('/api/chatbot', name: 'api_chatbot', methods: ['POST'])]
    public function chatbot(Request $request, ChatbotService $chatbotService, TicketRepository $ticketRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $message = $data['message'] ?? '';

        $tickets = $ticketRepository->findAllOrderedByDate();
        $chatbotService->setTickets($tickets);
        $response = $chatbotService->processMessage($message);

        return $this->json(['response' => $response]);
    }
}
