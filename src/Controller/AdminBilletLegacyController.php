<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminBilletLegacyController extends AbstractController
{
    #[Route('/admin/billets', name: 'admin_billet_index_legacy', methods: ['GET'])]
    public function redirectIndex(): Response
    {
        return $this->redirectToRoute('admin_billet_index');
    }
}

