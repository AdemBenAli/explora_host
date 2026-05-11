<?php

namespace App\Controller;

use App\Entity\Billet;
use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\LabelAlignment;
use Endroid\QrCode\Label\Font\OpenSans;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BilletPdfController extends AbstractController
{
    #[Route('/billet/{id}/ticket', name: 'user_billet_ticket_pdf', methods: ['GET'])]
    public function generateTicketPdf(Billet $billet): Response
    {
        // 1. Générer le QR Code (Compatibilité native stricte Endroid v6)
        $data = 'ID:' . $billet->getId() . '|TRANSPORT:' . $billet->getTransport()->getNumeroVol();
        $qrCode = new QrCode(data: $data, size: 200, margin: 10);

        $writer = new SvgWriter();
        $result = $writer->write($qrCode);
        $qrCodeDataUri = $result->getDataUri();

        // 2. Générer le PDF (Bundle Consistant Dompdf)
        // Encode le logo en base64 pour être certain qu'il s'affiche dans le PDF
        $logoPath = $this->getParameter('kernel.project_dir') . '/public/images/explora-logo.png';
        $logoBase64 = null;
        if (file_exists($logoPath)) {
            $type = pathinfo($logoPath, PATHINFO_EXTENSION);
            $data = file_get_contents($logoPath);
            $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        $html = $this->renderView('billet/ticket_pdf.html.twig', [
            'billet' => $billet,
            'qrCode' => $qrCodeDataUri,
            'logoPath' => $logoBase64,
        ]);

        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $output = $dompdf->output();
        $filename = sprintf('ticket_%s.pdf', $billet->getId());

        return new Response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="%s"', $filename),
        ]);
    }
}
