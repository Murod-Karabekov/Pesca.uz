<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\Product;
use App\Repository\CartRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/cart', name: 'app_cart_')]
#[IsGranted('ROLE_USER')]
class CartController extends AbstractController
{
    private function isAjax(Request $request): bool
    {
        return $request->isXmlHttpRequest() || 'xmlhttprequest' === mb_strtolower((string) $request->headers->get('X-Requested-With'));
    }

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
        $isAjax = $this->isAjax($request);

        if (!$this->isCsrfTokenValid('cart_add_' . $product->getId(), $request->request->get('_token'))) {
            if ($isAjax) {
                return $this->json(['success' => false, 'message' => 'Noto\'g\'ri CSRF token.'], Response::HTTP_BAD_REQUEST);
            }

            $this->addFlash('error', 'Noto\'g\'ri CSRF token.');
            return $this->redirectToRoute('app_product_show', ['id' => $product->getId()]);
        }

        $availableSizes = $product->getSize();
        $selectedSize = mb_strtoupper(trim((string) $request->request->get('size', '')));

        if ($availableSizes !== []) {
            if ($selectedSize === '' || !in_array($selectedSize, $availableSizes, true)) {
                if ($isAjax) {
                    return $this->json(['success' => false, 'message' => 'Iltimos, o\'lchamni tanlang.'], Response::HTTP_BAD_REQUEST);
                }

                $this->addFlash('error', 'Iltimos, o\'lchamni tanlang.');
                return $this->redirectToRoute('app_product_show', ['id' => $product->getId()]);
            }
        }

        if ($selectedSize === '') {
            $selectedSize = 'UNIVERSAL';
        }

        // Check if item already in cart
        $existingItem = $cartRepository->findExistingCartItem($this->getUser(), $product->getId(), $selectedSize);

        if ($existingItem) {
            $existingItem->setQuantity($existingItem->getQuantity() + 1);
        } else {
            $cartItem = new Cart();
            $cartItem->setUser($this->getUser());
            $cartItem->setProduct($product);
            $cartItem->setSize($selectedSize);
            $cartItem->setQuantity(1);
            $em->persist($cartItem);
        }

        $em->flush();

        if ($isAjax) {
            return $this->json([
                'success' => true,
                'message' => $product->getName() . ' savatga qo\'shildi!',
                'cartUrl' => $this->generateUrl('app_cart_index'),
            ]);
        }

        $this->addFlash('success', $product->getName() . ' savatga qo\'shildi!');
        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/update/{id}', name: 'update', methods: ['POST'])]
    public function update(
        Cart $cartItem,
        Request $request,
        CartRepository $cartRepository,
        EntityManagerInterface $em
    ): Response {
        $isAjax = $this->isAjax($request);

        if ($cartItem->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('cart_update_' . $cartItem->getId(), $request->request->get('_token'))) {
            if ($isAjax) {
                return $this->json(['success' => false, 'message' => 'Noto\'g\'ri CSRF token.'], Response::HTTP_BAD_REQUEST);
            }

            $this->addFlash('error', 'Noto\'g\'ri CSRF token.');
            return $this->redirectToRoute('app_cart_index');
        }

        $quantity = (int) $request->request->get('quantity', 1);
        if ($quantity < 1) {
            $quantity = 1;
        }

        $cartItem->setQuantity($quantity);
        $em->flush();

        if ($isAjax) {
            return $this->json([
                'success' => true,
                'quantity' => $cartItem->getQuantity(),
                'itemTotal' => $cartItem->getTotal(),
                'cartTotal' => $cartRepository->getCartTotal($this->getUser()),
            ]);
        }

        $this->addFlash('success', 'Savatcha yangilandi.');
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
            $this->addFlash('success', 'Mahsulot savatdan o\'chirildi.');
        }

        return $this->redirectToRoute('app_cart_index');
    }
}
