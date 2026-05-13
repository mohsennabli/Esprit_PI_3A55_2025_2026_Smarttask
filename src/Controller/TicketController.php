<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/ticket')]
class TicketController extends AbstractController
{
    #[Route('/', name: 'ticket_index', methods: ['GET'])]
    public function index(TicketRepository $repo, Request $request): Response
    {
        $search = $request->query->get('search');
        $statut = $request->query->get('statut');

        $tickets = $repo->filterTickets($search, $statut);
        $stats = $repo->countByStatut();

        return $this->render('ticket/index.html.twig', [
            'tickets' => $tickets,
            'stats' => $stats,
            'total' => count($tickets) // Total filtered tickets (or all)
        ]);
    }

    #[Route('/new', name: 'ticket_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $ticket = new Ticket();
            $ticket->setTitre($request->request->get('titre'));
            $ticket->setDescription($request->request->get('description'));
            $ticket->setStatut($request->request->get('statut'));
            $ticket->setPriorite($request->request->get('priorite'));
            $ticket->setDateCreation(new \DateTime());

            $em->persist($ticket);
            $em->flush();

            $this->addFlash('success', 'Ticket ajouté avec succès !');
            return $this->redirectToRoute('ticket_index');
        }

        return $this->render('ticket/new.html.twig');
    }

    #[Route('/{id}', name: 'ticket_show', methods: ['GET'])]
    public function show(Ticket $ticket): Response
    {
        return $this->render('ticket/show.html.twig', [
            'ticket' => $ticket
        ]);
    }

    #[Route('/{id}/edit', name: 'ticket_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Ticket $ticket, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $ticket->setTitre($request->request->get('titre'));
            $ticket->setDescription($request->request->get('description'));
            $ticket->setStatut($request->request->get('statut'));
            $ticket->setPriorite($request->request->get('priorite'));

            $em->flush();

            $this->addFlash('success', 'Ticket modifié avec succès !');
            return $this->redirectToRoute('ticket_index');
        }

        return $this->render('ticket/edit.html.twig', [
            'ticket' => $ticket
        ]);
    }

    #[Route('/{id}/delete', name: 'ticket_delete', methods: ['POST'])]
    public function delete(Ticket $ticket, EntityManagerInterface $em): Response
    {
        $em->remove($ticket);
        $em->flush();

        $this->addFlash('success', 'Ticket supprimé avec succès !');
        return $this->redirectToRoute('ticket_index');
    }
}
