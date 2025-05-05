<?php

namespace App\Service;

use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use Twig\Environment;

class MailerService
{
    private string $adminEmail;
    private MailerInterface $mailer;
    private Environment $templating;
    private VerifyEmailHelperInterface $verifyEmailHelper;

    public function __construct(
        #[Autowire('%admin_email%')] string $adminEmail,
        MailerInterface $mailer,
        Environment $templating,
        VerifyEmailHelperInterface $verifyEmailHelper,
    ) {
        $this->adminEmail = $adminEmail;
        $this->mailer = $mailer;
        $this->templating = $templating;
        $this->verifyEmailHelper = $verifyEmailHelper;
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendMail(string $to, string $subject, string $message): void
    {
        $email = (new NotificationEmail())
            ->from($this->adminEmail)
            ->to($to)
            ->subject($subject)
            ->text($message);

        $this->mailer->send($email);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendResetPasswordEmail(string $user, string $resetUrl): void
    {
        $email = (new NotificationEmail())
            ->from($this->adminEmail)
            ->to($user)
            ->subject('Réinitialisation de votre mot de passe')
            ->htmlTemplate('email/reset_password.html.twig') // Utilisation de htmlTemplate au lieu de html
            ->context([
                'resetUrl' => $resetUrl,
                'expiresAtMessageKey' => 'Le lien de réinitialisation expire dans 1 heure.',
                'expiresAtMessageData' => ['%count%' => 60], // 60 minutes
            ]);

        $this->mailer->send($email);
    }
}
