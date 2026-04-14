<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Service\ReferralService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/orders', name: 'admin_order_')]
#[IsGranted('ROLE_ADMIN')]
class OrderController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(OrderRepository $orderRepository): Response
    {
        return $this->render('admin/order/index.html.twig', [
            'orders' => $orderRepository->findNewOrders(),
            'historyCount' => $orderRepository->countHistoryOrders(),
        ]);
    }

    #[Route('/history', name: 'history')]
    public function history(Request $request, OrderRepository $orderRepository): Response
    {
        $query = trim((string) $request->query->get('q', ''));

        return $this->render('admin/order/history.html.twig', [
            'orders' => $orderRepository->findHistoryOrders($query),
            'query' => $query,
            'newCount' => $orderRepository->countNewOrders(),
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function show(Order $order): Response
    {
        return $this->render('admin/order/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/payment/approve', name: 'approve_payment', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function approvePayment(
        Order $order,
        Request $request,
        EntityManagerInterface $em,
        ReferralService $referralService,
    ): Response
    {
        if (!$this->isCsrfTokenValid('approve_order_' . $order->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Xavfsizlik xatosi.');
            return $this->redirectToRoute('admin_order_index');
        }

        if (!$order->canApprovePayment()) {
            $this->addFlash('warning', 'Bu buyurtma uchun to\'lov allaqachon yakuniy holatda.');
            return $this->redirectToRoute('admin_order_index');
        }

        $note = trim((string) $request->request->get('note', 'To\'lov tasdiqlandi'));

        $order->setPaymentStatus(Order::PAYMENT_STATUS_APPROVED);
        $order->setOrderStatus(Order::ORDER_STATUS_PAID);
        $order->setApprovedByAdmin($this->getUser());
        $order->setApprovedAt(new \DateTimeImmutable());
        $order->setAdminNote($note ?: 'To\'lov tasdiqlandi');

        if ($order->getUser() !== null && $order->getId() !== null) {
            $referralService->processProductReferral(
                $order->getUser(),
                $order->getSubtotalAmount(),
                $order->getId()
            );
        }

        $em->flush();

        $this->addFlash('success', sprintf('Buyurtma #%d to\'lovi tasdiqlandi.', $order->getId()));

        return $this->redirectToRoute('admin_order_index');
    }

    #[Route('/{id}/payment/reject', name: 'reject_payment', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function rejectPayment(Order $order, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('reject_order_' . $order->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Xavfsizlik xatosi.');
            return $this->redirectToRoute('admin_order_index');
        }

        if (!$order->canApprovePayment()) {
            $this->addFlash('warning', 'Bu buyurtma uchun to\'lov allaqachon yakuniy holatda.');
            return $this->redirectToRoute('admin_order_index');
        }

        $note = trim((string) $request->request->get('note', 'To\'lov rad etildi'));

        $order->setPaymentStatus(Order::PAYMENT_STATUS_REJECTED);
        $order->setOrderStatus(Order::ORDER_STATUS_CANCELED);
        $order->setApprovedByAdmin($this->getUser());
        $order->setApprovedAt(new \DateTimeImmutable());
        $order->setAdminNote($note ?: 'To\'lov rad etildi');

        $em->flush();

        $this->addFlash('warning', sprintf('Buyurtma #%d to\'lovi rad etildi.', $order->getId()));

        return $this->redirectToRoute('admin_order_index');
    }

    #[Route('/{id}/status', name: 'update_status', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function updateStatus(Order $order, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('order_status_' . $order->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Xavfsizlik xatosi.');
            return $this->redirectToRoute('admin_order_show', ['id' => $order->getId()]);
        }

        $newStatus = (string) $request->request->get('status', '');

        if (!$order->canTransitionTo($newStatus)) {
            $this->addFlash('error', sprintf(
                'Buyurtma holatini "%s" dan "%s" ga o\'tkazib bo\'lmaydi.',
                $order->getOrderStatusLabel(),
                $newStatus
            ));
            return $this->redirectToRoute('admin_order_show', ['id' => $order->getId()]);
        }

        $order->setOrderStatus($newStatus);
        $order->touch();
        $em->flush();

        $this->addFlash('success', sprintf(
            'Buyurtma #%d holati "%s" ga o\'zgartirildi.',
            $order->getId(),
            $order->getOrderStatusLabel()
        ));

        return $this->redirectToRoute('admin_order_show', ['id' => $order->getId()]);
    }
}
