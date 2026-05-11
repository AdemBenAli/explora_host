<?php

namespace App\Controller\Admin;

use App\Repository\HebergementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/hotels', name: 'admin_hotel_list_')]
class AdminHotelListController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(Request $request, HebergementRepository $hotelRepo): Response
    {
        $hotels = $hotelRepo->findAll();

        return $this->render('admin/hotel/index.html.twig', [
            'hotels' => $hotels,
        ]);
    }
}