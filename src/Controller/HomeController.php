<?php

namespace App\Controller;

use App\Repository\AnnouncementRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ProductRepository $productRepository, AnnouncementRepository $announcementRepository): Response
    {
        return $this->render('home/index.html.twig', [
            'announcements' => $announcementRepository->findActiveOrdered(),
            'banners' => $announcementRepository->findActiveBannersOrdered(),
            'featured_products' => $productRepository->findBy(['status' => true], ['createdAt' => 'DESC'], 4),
        ]);
    }
}
