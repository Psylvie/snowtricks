<?php

namespace App\Controller;

use App\Form\ForgotPasswordType;
use App\Form\ResetPasswordType;
use App\Repository\UserRepository;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MailerService $mailer,
        private readonly UserPasswordHasherInterface $passwordEncoder)
    {
    }

    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, Security $security): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        if ($error) {
            $errorMessage = 'Oups ! Les identifiants que vous avez saisis sont incorrects. Veuillez réessayer.';
        } else {
            $errorMessage = null;
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $errorMessage,
        ]);
    }

    #[IsGranted('IS_AUTHENTICATED')]
    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * @throws \Exception
     * @throws TransportExceptionInterface
     */
    #[Route('/forgotten-password', name: 'app_forgotten_password')]
    public function forgottenPassword(Request $request, UserRepository $userRepository): Response
    {
        $form = $this->createForm(ForgotPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $username = $form->get('username')->getData();
            $user = $userRepository->findOneBy(['username' => $username]);

            if (!$user) {
                $this->addFlash('danger', "Cet email n'existe pas)");

                return $this->redirectToRoute('app_forgotten_password');
            }
            $resetToken = bin2hex(random_bytes(32));
            $user->setToken($resetToken);
            $this->em->persist($user);
            $this->em->flush();

            $resetUrl = $this->generateUrl('app_reset_password', ['token' => $resetToken], UrlGeneratorInterface::ABSOLUTE_URL);

            try {
                $this->mailer->sendResetPasswordEmail($user->getEmail(), $resetUrl);
            } catch (TransportExceptionInterface $e) {
                $this->addFlash('danger', 'Une erreur est survenue lors de l\'envoi de l\'email');

                return $this->redirectToRoute('app_forgotten_password');
            }

            $this->addFlash('success', 'Un email de réinitialisation de mot de passe vous a été envoyé');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/forgotten_password.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function resetPassword(Request $request, string $token, UserRepository $userRepository): Response
    {
        $user = $userRepository->findOneBy(['token' => $token]);

        if (!$user) {
            $this->addFlash('danger', 'Token inconnu');

            return $this->redirectToRoute('app_login');
        }
        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword($this->passwordEncoder->hashPassword($user, $plainPassword));

            $this->em->persist($user);
            $this->em->flush();
            $this->addFlash('success', 'Mot de passe modifié avec succés !');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }
}
