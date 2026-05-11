<?php

namespace App\Service;

use App\Entity\Paiement;
use App\Entity\Panier;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class InvoicePdfService
{
    public function __construct(private readonly Environment $twig)
    {
    }

    public function generatePaymentInvoice(Paiement $paiement, Panier $panier, array $cartItems): string
    {
        $html = $this->twig->render('invoice/payment_invoice.html.twig', [
            'paiement' => $paiement,
            'panier' => $panier,
            'cartItems' => $cartItems,
            'generatedAt' => new \DateTime(),
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4');
        $dompdf->render();

        return $dompdf->output();
    }
}
