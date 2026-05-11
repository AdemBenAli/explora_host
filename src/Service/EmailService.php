<?php

namespace App\Service;

use App\Entity\Billet;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig
    ) {}

    public function sendTicketEmail(Billet $billet, string $pdfContent): void
    {
        $email = (new Email())
            ->from('noreply@explora-travel.com')
            ->to('client@example.com') // En production, utiliser $billet->getUser()->getEmail()
            ->subject('Votre billet Explora Travel - #' . $billet->getId())
            ->html($this->twig->render('emails/ticket.html.twig', [
                'billet' => $billet
            ]))
            ->attach($pdfContent, sprintf('ticket_%s.pdf', $billet->getId()), 'application/pdf');

        $this->mailer->send($email);
    }
}
