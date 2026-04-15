<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\CartRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/order', name: 'app_order_')]
#[IsGranted('ROLE_USER')]
class OrderController extends AbstractController
{
    private function buildLocationCode(string $locationLabel): string
    {
        $normalized = mb_strtolower(trim($locationLabel));
        $normalized = preg_replace('/[^a-z0-9]+/i', '_', $normalized ?? '');
        $normalized = trim((string) $normalized, '_');

        if ($normalized === '') {
            $normalized = substr(sha1($locationLabel), 0, 16);
        }

        return substr('custom_' . $normalized, 0, 50);
    }

    #[Route('/place', name: 'place_entry', methods: ['GET'])]
    public function placeEntry(): Response
    {
        $this->addFlash('warning', 'Buyurtma berish uchun savatchadagi formani yuboring.');

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/place', name: 'place', methods: ['POST'])]
    public function place(
        Request $request,
        CartRepository $cartRepository,
        EntityManagerInterface $em,
        LoggerInterface $logger,
    ): Response {
        if (!$this->isCsrfTokenValid('order_place', $request->request->get('_token'))) {
            $this->addFlash('error', 'Noto\'g\'ri CSRF token.');
            return $this->redirectToRoute('app_cart_index');
        }

        $user = $this->getUser();
        $selectedItemIds = array_values(array_unique(array_map(
            'intval',
            (array) $request->request->all('selected_items')
        )));

        if ($selectedItemIds === []) {
            $this->addFlash('error', 'Iltimos, buyurtma uchun kamida bitta mahsulotni belgilang.');
            return $this->redirectToRoute('app_cart_index');
        }

        $cartItems = $cartRepository->findByUserAndIds($user, $selectedItemIds);

        if (empty($cartItems)) {
            $this->addFlash('error', 'Savatchangiz bo\'sh.');
            return $this->redirectToRoute('app_cart_index');
        }

        $locationLabel = trim((string) $request->request->get('location_label', ''));
        if ($locationLabel === '') {
            $this->addFlash('error', 'Lokatsiyani kiriting.');
            return $this->redirectToRoute('app_cart_index');
        }

        if (mb_strlen($locationLabel) > 100) {
            $this->addFlash('error', 'Lokatsiya 100 belgidan oshmasligi kerak.');
            return $this->redirectToRoute('app_cart_index');
        }

        $locationCode = $this->buildLocationCode($locationLabel);

        $notes = trim((string) $request->request->get('notes', ''));
        if (mb_strlen($notes) > 1000) {
            $this->addFlash('error', 'Izoh 1000 belgidan oshmasligi kerak.');
            return $this->redirectToRoute('app_cart_index');
        }

        $conn = $em->getConnection();
        $conn->beginTransaction();

        try {
            $order = new Order();
            $order->setUser($user);
            $order->setCustomerFullName((string) $user->getFullName());
            $order->setCustomerPhone((string) $user->getPhone());
            $order->setLocationCode($locationCode);
            $order->setLocationLabel($locationLabel);
            $order->setNotes($notes !== '' ? $notes : null);
            $order->setPaymentMethod('manual');

            $subtotal = '0.00';

            foreach ($cartItems as $cartItem) {
                $product = $cartItem->getProduct();
                $unitPrice = (string) $product->getPrice();
                $quantity = $cartItem->getQuantity();
                $lineTotal = bcmul($unitPrice, (string) $quantity, 2);

                $item = new OrderItem();
                $item->setOrder($order);
                $item->setProduct($product);
                $item->setProductNameSnapshot((string) $product->getName());
                $item->setProductImageSnapshot($product->getImageSrc());
                $item->setSelectedSize($cartItem->getSize() !== 'UNIVERSAL' ? $cartItem->getSize() : null);
                $item->setUnitPrice($unitPrice);
                $item->setQuantity($quantity);
                $item->setLineTotal($lineTotal);
                $item->setVendor($product->getVendor());

                $order->addItem($item);
                $subtotal = bcadd($subtotal, $lineTotal, 2);
                $em->persist($item);
            }

            $order->setSubtotalAmount($subtotal);
            $em->persist($order);

            $cartRepository->removeByUserAndIds($user, array_map(
                static fn ($item) => $item->getId(),
                $cartItems
            ));
            $em->flush();
            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollBack();
            $logger->error('Order placement failed', [
                'user_id' => $user?->getId(),
                'exception' => $e,
            ]);

            $this->addFlash('error', 'Buyurtma yaratishda xatolik yuz berdi. Qaytadan urinib ko\'ring.');

            return $this->redirectToRoute('app_cart_index');
        }

        return $this->redirectToRoute('app_order_success', ['id' => $order->getId()]);
    }

    #[Route('/success/{id}', name: 'success', requirements: ['id' => '\\d+'])]
    public function success(Order $order): Response
    {
        if ($order->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('order/success.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/history', name: 'history')]
    public function history(OrderRepository $orderRepository): Response
    {
        return $this->render('order/history.html.twig', [
            'orders' => $orderRepository->findByUserOrderedByDate($this->getUser()),
        ]);
    }
}
