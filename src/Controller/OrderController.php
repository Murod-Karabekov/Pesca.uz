<?php

namespace App\Controller;

use App\Repository\CartRepository;
use App\Service\GoogleFormService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/order', name: 'app_order_')]
#[IsGranted('ROLE_USER')]
class OrderController extends AbstractController
{
    #[Route('/place', name: 'place', methods: ['POST'])]
    public function place(
        Request $request,
        CartRepository $cartRepository,
        GoogleFormService $googleFormService,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isCsrfTokenValid('order_place', $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_cart_index');
        }

        $user = $this->getUser();
        $cartItems = $cartRepository->findByUser($user);

        if (empty($cartItems)) {
            $this->addFlash('error', 'Your cart is empty.');
            return $this->redirectToRoute('app_cart_index');
        }

        // Submit each cart item to Google Form
        foreach ($cartItems as $item) {
            $googleFormService->submitOrder(
                $user->getFullName(),
                $user->getPhone(),
                $item->getProduct()->getName(),
                $item->getSize(),
                $item->getQuantity()
            );
        }

        // Clear cart
        $cartRepository->clearCart($user);

        return $this->redirectToRoute('app_order_success');
    }

    #[Route('/success', name: 'success')]
    public function success(): Response
    {
        return $this->render('order/success.html.twig');
    }
}
