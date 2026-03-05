<?php

namespace App\Controller;

use App\Entity\Formation;
use App\Form\FormationType;
use App\Repository\FormationRepository;
use App\Service\GoogleCalendarService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Snappy\Pdf;

#[IsGranted('ROLE_USER')]
#[Route('/formation')]
class FormationController extends AbstractController
{

    #[Route('/', name: 'app_formation_index', methods: ['GET'])]
    public function index(FormationRepository $repository): Response
    {
        $formations = $repository->findBy([], ['dateDebut' => 'DESC']);

        return $this->render('front/formation/index.html.twig', [
            'formations' => $formations,
        ]);
    }


    #[Route('/new', name: 'app_formation_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_MANAGER', message: 'Seuls les managers peuvent créer des formations.')]
    public function new(Request $request, EntityManagerInterface $em, GoogleCalendarService $calendarService): Response
    {
        $formation = new Formation();
        $form = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($formation);
            $em->flush();

            // Create a Google Calendar event and store its ID
            $eventId = $calendarService->createEvent($formation);
            if ($eventId) {
                $formation->setGoogleEventId($eventId);
                $em->flush();
                $this->addFlash('success', 'Formation créée avec succès. L\'événement a été ajouté à Google Calendar.');
            } else {
                $this->addFlash('warning', 'Formation créée, mais l\'événement Google Calendar n\'a pas pu être créé. Vérifiez GOOGLE_APPLICATION_CREDENTIALS et les logs (var/log/dev.log).');
            }
            return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()]);
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Veuillez corriger les erreurs dans le formulaire.');
        }

        return $this->render('front/formation/new.html.twig', [
            'formation' => $formation,
            'form'      => $form,
        ]);
    }


    #[Route('/{id}', name: 'app_formation_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Formation $formation): Response
    {
        return $this->render('front/formation/show.html.twig', [
            'formation' => $formation,
        ]);
    }


    #[Route('/{id}/edit', name: 'app_formation_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_MANAGER', message: 'Seuls les managers peuvent modifier les formations.')]
    public function edit(Request $request, Formation $formation, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Formation mise à jour.');
            return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()]);
        }

        return $this->render('front/formation/edit.html.twig', [
            'formation' => $formation,
            'form'      => $form,
        ]);
    }


    #[Route('/{id}/inscriptions/pdf', name: 'app_formation_inscriptions_pdf', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_MANAGER', message: 'Seuls les managers peuvent télécharger la liste des inscriptions.')]
    public function inscriptionsPdf(Formation $formation, Pdf $pdf): Response
    {
        $html = $this->renderView('pdf/formation_inscriptions.html.twig', [
            'formation' => $formation,
        ]);

        $filename = sprintf('inscriptions_formation_%d.pdf', $formation->getId());

        $pdfContent = $pdf->getOutputFromHtml($html);

        return new Response(
            $pdfContent,
            Response::HTTP_OK,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => sprintf('%s; filename="%s"', ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename),
            ]
        );
    }


    #[Route('/{id}/delete', name: 'app_formation_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_MANAGER', message: 'Seuls les managers peuvent supprimer des formations.')]
    public function delete(Request $request, Formation $formation, EntityManagerInterface $em, GoogleCalendarService $calendarService): Response
    {
        if ($this->isCsrfTokenValid('delete' . $formation->getId(), (string) $request->request->get('_token'))) {
            // Delete associated Google Calendar event before removing the formation
            $googleEventId = $formation->getGoogleEventId();
            if ($googleEventId) {
                $calendarService->deleteEvent($googleEventId);
            }
            $em->remove($formation);
            $em->flush();
            $this->addFlash('success', 'Formation supprimée.');
        }

        return $this->redirectToRoute('app_formation_index');
    }
}
