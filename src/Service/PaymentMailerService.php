<?php

namespace App\Service;

use App\Entity\Paiement;
use App\Entity\Panier;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class PaymentMailerService
{
    private const TEST_RECIPIENT = 'adembenali2004@gmail.com';

    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly InvoicePdfService $invoicePdfService,
    ) {
    }

    public function sendPaymentInvoice(Paiement $paiement, Panier $panier, array $cartItems): void
    {
        $reference = $paiement->getReferenceTransaction() ?? ('PAY-' . ($paiement->getId() ?? 'N/A'));
        $pdfContent = $this->invoicePdfService->generatePaymentInvoice($paiement, $panier, $cartItems);

        $email = (new TemplatedEmail())
            ->from(new Address('adembenali2004@gmail.com', 'Explora'))
            ->to(new Address(self::TEST_RECIPIENT))
            ->subject('Payment Confirmation - ' . $reference)
            ->htmlTemplate('emails/payment_confirmation.html.twig')
            ->context([
                'paiement' => $paiement,
                'panier' => $panier,
                'cartItems' => $cartItems,
                'reference' => $reference,
            ])
            ->attach($pdfContent, 'Invoice_' . $reference . '.pdf', 'application/pdf');

        $this->mailer->send($email);
    }
}
