<?php

namespace App\Controller;

use App\Entity\Manager;
use App\Entity\Collaborator;
use App\Form\ManagerType;
use App\Form\CollaboratorType;
use App\Repository\ManagerRepository;
use App\Repository\UserRepository;
use App\Service\EnterpriseCodeGenerator;
use App\Service\FaceRecognitionService;
use App\Security\LoginFormAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class AuthController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly FaceRecognitionService $faceRecognitionService,
        private readonly LoggerInterface $logger,
        private readonly Security $security,

        private readonly EnterpriseCodeGenerator $enterpriseCodeGenerator,
        private readonly EntityManagerInterface $entityManager,
        private readonly ManagerRepository $managerRepository
    ) {
    }

    #[Route('/login/face', name: 'app_face_login', methods: ['POST'])]
    public function faceLogin(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        $csrfToken = (string) ($payload['_csrf_token'] ?? '');
        if (!$this->isCsrfTokenValid('face_login', $csrfToken)) {
            return $this->json(['message' => 'Invalid request token.'], Response::HTTP_FORBIDDEN);
        }

        $email = trim((string) ($payload['email'] ?? ''));
        $image = (string) ($payload['image'] ?? '');

        if ('' === $email || '' === $image) {
            return $this->json(['message' => 'Email and face image are required.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user || !$user->isEnabled()) {
            $this->logger->warning('Face ID login failed: user not found or disabled', [
                'email' => $email,
                'ip' => $request->getClientIp(),
            ]);

            return $this->json(['message' => 'Invalid credentials.'], Response::HTTP_UNAUTHORIZED);
        }

        $storedEmbedding = $user->getFaceEmbedding();
        if (empty($storedEmbedding)) {
            return $this->json(['message' => 'Face ID not configured.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->faceRecognitionService->verify($image, $storedEmbedding);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        if (!$result['match']) {
            $this->logger->info('Face ID login failed: no match', [
                'email' => $email,
                'ip' => $request->getClientIp(),
                'similarity' => $result['similarity'],
            ]);

            return $this->json([
                'message' => 'Face does not match.',
                'similarity' => $result['similarity'],
            ], Response::HTTP_UNAUTHORIZED);
        }

        $this->security->login($user, LoginFormAuthenticator::class, 'main');

        $this->logger->info('Face ID login success', [
            'email' => $email,
            'ip' => $request->getClientIp(),
            'similarity' => $result['similarity'],
        ]);

        return $this->json([
            'message' => 'Login success',
            'similarity' => $result['similarity'],
            'redirect' => $this->generateUrl('app_dashboard_index'),
        ]);
    }

    /**
     * Web login page
     * GET: Display login form
     * POST: Handled by Symfony Security (form_login)
     */
    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function loginPage(AuthenticationUtils $authenticationUtils): Response
    {
        // Redirect authenticated users to dashboard
        if ($this->getUser()) {
            $response = $this->redirectToRoute('app_dashboard_index');
            // Prevent caching of redirect
            $response->setCache([
                'must_revalidate' => true,
                'no_cache' => true,
                'no_store' => true,
                'max_age' => 0,
            ]);
            return $response;
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        $response = $this->render('auth/login.html.twig', [
            'error' => $error,
            'last_username' => $lastUsername,
        ]);

        // Prevent browser from caching the login page
        $response->setCache([
            'must_revalidate' => true,
            'no_cache' => true,
            'no_store' => true,
            'max_age' => 0,
        ]);

        return $response;
    }



    // Removed old register method as it's now handled by RegistrationController

    /**
     * Forgot password page
     */
    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(Request $request, MailerInterface $mailer): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard_index');
        }

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $user = $this->userRepository->findOneBy(['email' => $email]);

            if ($user && $user->isEnabled()) {
                // Generate token
                $token = bin2hex(random_bytes(32));
                $user->setResetToken($token);
                $user->setResetTokenExpiresAt(new \DateTimeImmutable('+1 hour'));

                $this->entityManager->flush();

                // Send email
                $resetUrl = $this->generateUrl('app_reset_password', ['token' => $token], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);
                $username = $user->getName() ?? $user->getEmail();
                
                $body = "Hello $username,\n\n"
                    . "You have requested to reset your password for your SmartTask Manager account.\n\n"
                    . "Please click the link below to set a new password. This link will expire in 1 hour:\n"
                    . $resetUrl . "\n\n"
                    . "If you did not request this, please ignore this email.\n\n"
                    . "© " . date('Y') . " SmartTask Manager. All rights reserved.";

                $email = (new Email())
                    ->from('mohsennabli321@gmail.com')
                    ->to($user->getEmail())
                    ->subject('Your password reset request')
                    ->text($body);

                $mailer->send($email);
            }

            // Always show the same message for security
            $this->addFlash('success', 'If an account exists for this email, you will receive a reset link shortly.');
            return $this->redirectToRoute('app_forgot_password');
        }

        $response = $this->render('auth/forgot_password.html.twig');
        $response->setCache(['must_revalidate' => true, 'no_cache' => true, 'no_store' => true, 'max_age' => 0]);
        return $response;
    }

    /**
     * Reset password page
     */
    #[Route('/reset-password/{token}', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(string $token, Request $request): Response
    {
        $user = $this->userRepository->findOneBy(['resetToken' => $token]);

        if (!$user || $user->isResetTokenExpired()) {
            $this->addFlash('error', 'Invalid or expired reset token.');
            return $this->redirectToRoute('app_forgot_password');
        }

        if ($request->isMethod('POST')) {
            $password = $request->request->get('password');
            $confirmPassword = $request->request->get('confirm_password');

            if ($password !== $confirmPassword) {
                $this->addFlash('error', 'Passwords do not match.');
            } elseif (strlen($password) < 8) {
                $this->addFlash('error', 'Password must be at least 8 characters long.');
            } else {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
                $user->setPassword($hashedPassword);
                $user->setResetToken(null);
                $user->setResetTokenExpiresAt(null);
                
                $this->entityManager->flush();

                $this->addFlash('success', 'Password reset successfully. You can now login.');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('auth/reset_password.html.twig', [
            'token' => $token
        ]);
    }

    /**
     * Logout
     */
    #[Route('/logout', name: 'app_logout', methods: ['GET', 'POST'])]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
