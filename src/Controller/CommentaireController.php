<?php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Entity\Ticket;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/commentaire')]
class CommentaireController extends AbstractController
{
    #[Route('/{ticketId}/new', name: 'commentaire_new', methods: ['POST'])]
    public function new(Request $request, int $ticketId, TicketRepository $ticketRepository, EntityManagerInterface $em): Response
    {
        // Récupérer le ticket par son ID
        $ticket = $ticketRepository->find($ticketId);

        if (!$ticket) {
            $this->addFlash('danger', 'Ticket non trouvé !');
            return $this->redirectToRoute('ticket_index');
        }

        $contenu = $request->request->get('contenu');

        if ($contenu && trim($contenu) !== '') {
            $commentaire = new Commentaire();
            $commentaire->setContenu(trim($contenu));
            $commentaire->setTicket($ticket);

            $em->persist($commentaire);
            $em->flush();

            $this->addFlash('success', 'Commentaire ajouté avec succès !');
        } else {
            $this->addFlash('danger', 'Le commentaire ne peut pas être vide.');
        }

        return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
    }

    #[Route('/{id}/delete', name: 'commentaire_delete', methods: ['POST'])]
    public function delete(Commentaire $commentaire, EntityManagerInterface $em): Response
    {
        $ticketId = $commentaire->getTicket()->getId();
        $em->remove($commentaire);
        $em->flush();

        $this->addFlash('success', 'Commentaire supprimé avec succès !');
        return $this->redirectToRoute('ticket_show', ['id' => $ticketId]);
    }
}
