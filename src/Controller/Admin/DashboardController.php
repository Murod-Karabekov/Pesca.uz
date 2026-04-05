<?php

namespace App\Controller\Admin;

use App\Repository\CategoryRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\UserProfileRepository;
use App\Repository\UserRepository;
use App\Repository\WithdrawalRepository;
use Doctrine\ORM\EntityManagerInterface;
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
        OrderRepository $orderRepository,
        UserRepository $userRepository,
        CategoryRepository $categoryRepository,
        UserProfileRepository $userProfileRepository,
        WithdrawalRepository $withdrawalRepository,
        EntityManagerInterface $em,
    ): Response {
        // Tarif a'zolari soni
        $memberCount = (int) $em->createQuery(
            "SELECT COUNT(u.id) FROM App\Entity\User u WHERE u.currentPlan != 'free'"
        )->getSingleScalarResult();

        return $this->render('admin/dashboard.html.twig', [
            'productCount' => $productRepository->count([]),
            'activeProductCount' => $productRepository->count(['status' => true]),
            'userCount' => $userRepository->count([]),
            'categoryCount' => $categoryRepository->count([]),
            'smartStyleUsers' => $userProfileRepository->count([]),
            'memberCount' => $memberCount,
            'orderCount' => $orderRepository->count([]),
            'pendingOrderPayments' => $orderRepository->count(['paymentStatus' => 'pending']),
            'pendingWithdrawals' => count($withdrawalRepository->findPending()),
        ]);
    }
}
