<?php

namespace App\Service;

use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
class MailerService
{
    public function __construct(
        #[Autowire('%admin_email%')] private string $adminEmail,
        private readonly MailerInterface $mailer

    ) {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendMail(): void
    {
        $email = (new NotificationEmail())
            ->from($this->adminEmail)
            ->to($this->adminEmail)
            ->subject('$subject')
            ->text('$message');

        $this->mailer->send($email);
    }
}
