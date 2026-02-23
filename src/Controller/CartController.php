<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\Product;
use App\Repository\CartRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/cart', name: 'app_cart_')]
#[IsGranted('ROLE_USER')]
class CartController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(CartRepository $cartRepository): Response
    {
        $cartItems = $cartRepository->findByUser($this->getUser());
        $total = $cartRepository->getCartTotal($this->getUser());

        return $this->render('cart/index.html.twig', [
            'cartItems' => $cartItems,
            'total' => $total,
        ]);
    }

    #[Route('/add/{id}', name: 'add', methods: ['POST'])]
    public function add(
        Product $product,
        Request $request,
        CartRepository $cartRepository,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isCsrfTokenValid('cart_add_' . $product->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_product_show', ['id' => $product->getId()]);
        }

        $size = $request->request->get('size');
        if (!$size || !in_array($size, $product->getSize())) {
            $this->addFlash('error', 'Please select a valid size.');
            return $this->redirectToRoute('app_product_show', ['id' => $product->getId()]);
        }

        // Check if item already in cart
        $existingItem = $cartRepository->findExistingCartItem($this->getUser(), $product->getId(), $size);

        if ($existingItem) {
            $existingItem->setQuantity($existingItem->getQuantity() + 1);
        } else {
            $cartItem = new Cart();
            $cartItem->setUser($this->getUser());
            $cartItem->setProduct($product);
            $cartItem->setSize($size);
            $cartItem->setQuantity(1);
            $em->persist($cartItem);
        }

        $em->flush();

        $this->addFlash('success', $product->getName() . ' added to cart!');
        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/update/{id}', name: 'update', methods: ['POST'])]
    public function update(
        Cart $cartItem,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        if ($cartItem->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('cart_update_' . $cartItem->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_cart_index');
        }

        $quantity = (int) $request->request->get('quantity', 1);
        if ($quantity < 1) {
            $quantity = 1;
        }

        $cartItem->setQuantity($quantity);
        $em->flush();

        $this->addFlash('success', 'Cart updated.');
        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/remove/{id}', name: 'remove', methods: ['POST'])]
    public function remove(
        Cart $cartItem,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        if ($cartItem->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('cart_remove_' . $cartItem->getId(), $request->request->get('_token'))) {
            $em->remove($cartItem);
            $em->flush();
            $this->addFlash('success', 'Item removed from cart.');
        }

        return $this->redirectToRoute('app_cart_index');
    }
}
