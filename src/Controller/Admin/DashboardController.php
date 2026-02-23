<?php

namespace App\Controller\Admin;

use App\Repository\ProductRepository;
use App\Repository\TailorRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin', name: 'admin_')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'dashboard')]
    public function index(
        ProductRepository $productRepository,
        TailorRepository $tailorRepository,
        UserRepository $userRepository
    ): Response {
        return $this->render('admin/dashboard.html.twig', [
            'productCount' => $productRepository->count([]),
            'activeProductCount' => $productRepository->count(['status' => true]),
            'tailorCount' => $tailorRepository->count([]),
            'userCount' => $userRepository->count([]),
        ]);
    }
}
