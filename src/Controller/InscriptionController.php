<?php

namespace App\Controller;

use App\Entity\Inscription;
use App\Form\InscriptionType;
use App\Repository\InscriptionRepository;
use App\Service\FormationInscriptionMailer;
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
#[Route('/inscription')]
class InscriptionController extends AbstractController
{
    public function __construct(
        private readonly FormationInscriptionMailer $mailer,
        private readonly GoogleCalendarService $calendarService,
    ) {
    }

    #[Route('/', name: 'app_inscription_index', methods: ['GET'])]
    public function index(InscriptionRepository $repository): Response
    {
        $inscriptions = $repository->findBy([], ['dateInscription' => 'DESC']);

        return $this->render('front/inscription/index.html.twig', [
            'inscriptions' => $inscriptions,
        ]);
    }


    #[Route('/new', name: 'app_inscription_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_MANAGER', message: 'Seuls les managers peuvent créer des inscriptions.')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $inscription = new Inscription();
        $form = $this->createForm(InscriptionType::class, $inscription);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formation = $inscription->getFormation();
            if ($formation && !$formation->acceptsNewInscriptions()) {
                $this->addFlash('error', 'Cette formation n\'accepte plus de nouvelles inscriptions (terminée, passée ou capacité atteinte).');
                return $this->render('front/inscription/new.html.twig', [
                    'inscription' => $inscription,
                    'form'        => $form,
                ]);
            }
            $em->persist($inscription);
            $em->flush();

            $this->mailer->sendInscriptionConfirmation($inscription);

            // Add user as attendee to the Google Calendar event if one exists
            $formation   = $inscription->getFormation();
            $user        = $inscription->getUser();
            $googleEventId = $formation?->getGoogleEventId();
            if ($googleEventId && $user?->getEmail()) {
                $this->calendarService->addAttendeeToEvent($googleEventId, $user->getEmail());
            }

            $this->addFlash('success', 'Inscription créée avec succès. Un email de confirmation a été envoyé.');
            return $this->redirectToRoute('app_inscription_show', ['id' => $inscription->getId()]);
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Veuillez corriger les erreurs dans le formulaire.');
        }

        return $this->render('front/inscription/new.html.twig', [
            'inscription' => $inscription,
            'form'        => $form,
        ]);
    }


    #[Route('/{id}', name: 'app_inscription_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Inscription $inscription): Response
    {
        return $this->render('front/inscription/show.html.twig', [
            'inscription' => $inscription,
        ]);
    }


    #[Route('/{id}/edit', name: 'app_inscription_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_MANAGER', message: 'Seuls les managers peuvent modifier les inscriptions.')]
    public function edit(Request $request, Inscription $inscription, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(InscriptionType::class, $inscription);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Inscription mise à jour.');
            return $this->redirectToRoute('app_inscription_show', ['id' => $inscription->getId()]);
        }

        return $this->render('front/inscription/edit.html.twig', [
            'inscription' => $inscription,
            'form'        => $form,
        ]);
    }


    #[Route('/{id}/certificate/pdf', name: 'app_inscription_certificate_pdf', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_MANAGER', message: 'Seuls les managers peuvent générer les certificats.')]
    public function certificatePdf(Inscription $inscription, Pdf $pdf): Response
    {
        $html = $this->renderView('pdf/certificate.html.twig', [
            'inscription' => $inscription,
        ]);

        $filename = sprintf(
            'certificat_%s_formation_%d.pdf',
            $inscription->getUser()?->getIdUser() ?? $inscription->getId(),
            $inscription->getFormation()?->getId() ?? 0
        );

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


    #[Route('/{id}/delete', name: 'app_inscription_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_MANAGER', message: 'Seuls les managers peuvent supprimer des inscriptions.')]
    public function delete(Request $request, Inscription $inscription, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $inscription->getId(), (string) $request->request->get('_token'))) {
            $em->remove($inscription);
            $em->flush();
            $this->addFlash('success', 'Inscription supprimée.');
        }

        return $this->redirectToRoute('app_inscription_index');
    }
}
