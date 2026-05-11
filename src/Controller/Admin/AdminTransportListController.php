<?php

namespace App\Controller\Admin;

use App\Repository\TransportRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/transports', name: 'admin_transport_list_')]
class AdminTransportListController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(Request $request, TransportRepository $transportRepo): Response
    {
        $transports = $transportRepo->findAll();

        return $this->render('admin/transport/index.html.twig', [
            'transports' => $transports,
        ]);
    }
}