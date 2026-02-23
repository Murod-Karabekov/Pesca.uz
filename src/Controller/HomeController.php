<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Repository\TailorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ProductRepository $productRepository, TailorRepository $tailorRepository): Response
    {
        return $this->render('home/index.html.twig', [
            'featured_products' => $productRepository->findBy(['status' => true], ['createdAt' => 'DESC'], 4),
            'tailors' => $tailorRepository->findBy([], ['createdAt' => 'DESC'], 3),
        ]);
    }
}
