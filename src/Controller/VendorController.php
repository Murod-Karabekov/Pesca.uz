<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Product;
use App\Entity\Vendor;
use App\Entity\VendorTransaction;
use App\Repository\CategoryRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\VendorRepository;
use App\Repository\VendorTransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/vendor', name: 'vendor_')]
#[IsGranted('ROLE_VENDOR')]
class VendorController extends AbstractController
{
    /** Joriy foydalanuvchiga biriktirilgan do'konni qaytaradi yoki 403 */
    private function getMyVendor(VendorRepository $vendorRepo): Vendor
    {
        $vendor = $vendorRepo->findOneBy(['owner' => $this->getUser()]);
        if (!$vendor) {
            throw $this->createAccessDeniedException('Siz hech qaysi do\'konga biriktirilmagansiz.');
        }
        return $vendor;
    }

    // ─── Dashboard ───────────────────────────────────────────────────────────

    #[Route('', name: 'dashboard')]
    public function dashboard(
        VendorRepository $vendorRepo,
        ProductRepository $productRepo,
        VendorTransactionRepository $txRepo,
    ): Response {
        $vendor = $this->getMyVendor($vendorRepo);

        $pendingCount   = $productRepo->count(['vendor' => $vendor, 'publishStatus' => Product::PUBLISH_STATUS_PENDING]);
        $publishedCount = $productRepo->count(['vendor' => $vendor, 'publishStatus' => Product::PUBLISH_STATUS_PUBLISHED]);
        $recentTx       = $txRepo->findBy(['vendor' => $vendor], ['createdAt' => 'DESC'], 5);
        $unsettled      = $txRepo->sumUnsettledCommission($vendor);

        return $this->render('vendor/dashboard.html.twig', [
            'vendor'         => $vendor,
            'pendingCount'   => $pendingCount,
            'publishedCount' => $publishedCount,
            'recentTx'       => $recentTx,
            'unsettledCommission' => $unsettled,
        ]);
    }

    // ─── Mahsulotlar ─────────────────────────────────────────────────────────

    #[Route('/products', name: 'products')]
    public function products(
        VendorRepository $vendorRepo,
        ProductRepository $productRepo,
    ): Response {
        $vendor   = $this->getMyVendor($vendorRepo);
        $products = $productRepo->findBy(['vendor' => $vendor], ['createdAt' => 'DESC']);

        return $this->render('vendor/products.html.twig', [
            'vendor'   => $vendor,
            'products' => $products,
        ]);
    }

    #[Route('/products/new', name: 'product_new', methods: ['GET', 'POST'])]
    public function productNew(
        Request $request,
        VendorRepository $vendorRepo,
        CategoryRepository $categoryRepo,
        EntityManagerInterface $em,
    ): Response {
        $vendor = $this->getMyVendor($vendorRepo);

        if ($request->isMethod('POST')) {
            $name  = trim($request->request->get('name', ''));
            $price = trim($request->request->get('price', ''));
            $desc  = trim($request->request->get('description', ''));
            $sizes = $request->request->all('sizes');
            $catId = $request->request->get('category');
            $image = trim($request->request->get('image', ''));

            if (!$name || !$price || !is_numeric($price) || (float)$price <= 0) {
                $this->addFlash('error', 'Nomi va to\'g\'ri narxni kiriting.');
                return $this->redirectToRoute('vendor_product_new');
            }

            $product = new Product();
            $product->setName($name);
            $product->setPrice($price);
            $product->setDescription($desc ?: null);
            $product->setSize($sizes ?: []);
            $product->setImage($image ?: null);
            $product->setVendor($vendor);
            $product->setPublishStatus(Product::PUBLISH_STATUS_PENDING);
            $product->setStatus(false); // admin tasdiqlaydi

            if ($catId) {
                $cat = $categoryRepo->find((int)$catId);
                if ($cat) {
                    $product->setCategory($cat);
                }
            }

            $em->persist($product);
            $em->flush();

            $this->addFlash('success', 'Mahsulot yuborildi. Admin tekshirgandan so\'ng saytda ko\'rinadi.');
            return $this->redirectToRoute('vendor_products');
        }

        $categories = $categoryRepo->findBy([], ['name' => 'ASC']);

        return $this->render('vendor/product_new.html.twig', [
            'vendor'     => $vendor,
            'categories' => $categories,
            'sizes'      => Product::SIZES,
        ]);
    }

    // ─── Buyurtmalar ─────────────────────────────────────────────────────────

    #[Route('/orders', name: 'orders')]
    public function orders(
        VendorRepository $vendorRepo,
        VendorTransactionRepository $txRepo,
        EntityManagerInterface $em,
    ): Response {
        $vendor = $this->getMyVendor($vendorRepo);

        // Shu do'konga tegishli barcha order itemlar
        $allItems = $em->createQuery(
            'SELECT oi, o FROM App\Entity\OrderItem oi
             JOIN oi.order o
             WHERE oi.vendor = :vendor
             ORDER BY o.createdAt DESC'
        )->setParameter('vendor', $vendor)->getResult();

        $confirmedTxs = $txRepo->findBy(['vendor' => $vendor]);
        $txByOrderId = [];
        foreach ($confirmedTxs as $tx) {
            $txByOrderId[$tx->getOrder()->getId()] = $tx;
        }

        $orderCards = [];
        foreach ($allItems as $item) {
            $order = $item->getOrder();
            $orderId = $order->getId();

            if (!isset($orderCards[$orderId])) {
                $orderCards[$orderId] = [
                    'order' => $order,
                    'items' => [],
                    'total' => '0.00',
                ];
            }

            $orderCards[$orderId]['items'][] = $item;
            $orderCards[$orderId]['total'] = bcadd($orderCards[$orderId]['total'], (string)$item->getLineTotal(), 2);
        }

        $activeOrders = [];

        foreach ($orderCards as $orderId => $card) {
            $order = $card['order'];
            $isFinal = isset($txByOrderId[$orderId])
                || $order->getPaymentStatus() !== Order::PAYMENT_STATUS_PENDING
                || in_array($order->getOrderStatus(), [Order::ORDER_STATUS_COMPLETED, Order::ORDER_STATUS_CANCELED], true);

            if (!$isFinal) {
                $activeOrders[] = $card;
            }
        }

        return $this->render('vendor/orders.html.twig', [
            'vendor' => $vendor,
            'activeOrders' => $activeOrders,
        ]);
    }

    #[Route('/orders/history', name: 'orders_history')]
    public function ordersHistory(
        VendorRepository $vendorRepo,
        VendorTransactionRepository $txRepo,
        EntityManagerInterface $em,
    ): Response {
        $vendor = $this->getMyVendor($vendorRepo);

        $allItems = $em->createQuery(
            'SELECT oi, o FROM App\Entity\OrderItem oi
             JOIN oi.order o
             WHERE oi.vendor = :vendor
             ORDER BY o.createdAt DESC'
        )->setParameter('vendor', $vendor)->getResult();

        $confirmedTxs = $txRepo->findBy(['vendor' => $vendor]);
        $txByOrderId = [];
        foreach ($confirmedTxs as $tx) {
            $txByOrderId[$tx->getOrder()->getId()] = $tx;
        }

        $orderCards = [];
        foreach ($allItems as $item) {
            $order = $item->getOrder();
            $orderId = $order->getId();

            if (!isset($orderCards[$orderId])) {
                $orderCards[$orderId] = [
                    'order' => $order,
                    'items' => [],
                    'total' => '0.00',
                ];
            }

            $orderCards[$orderId]['items'][] = $item;
            $orderCards[$orderId]['total'] = bcadd($orderCards[$orderId]['total'], (string)$item->getLineTotal(), 2);
        }

        $historyOrders = [];
        foreach ($orderCards as $orderId => $card) {
            $order = $card['order'];
            $isFinal = isset($txByOrderId[$orderId])
                || $order->getPaymentStatus() !== Order::PAYMENT_STATUS_PENDING
                || in_array($order->getOrderStatus(), [Order::ORDER_STATUS_COMPLETED, Order::ORDER_STATUS_CANCELED], true);

            if ($isFinal) {
                $card['tx'] = $txByOrderId[$orderId] ?? null;
                $historyOrders[] = $card;
            }
        }

        return $this->render('vendor/orders_history.html.twig', [
            'vendor' => $vendor,
            'historyOrders' => $historyOrders,
        ]);
    }

    /**
     * Do'konchi "Tayyor — yetkazib berdim" tugmasini bosadi va summa kiritadi.
     * Bu VendorTransaction yozyizib, do'kon virtual kassasiga qo'shadi.
     */
    #[Route('/orders/{orderId}/confirm', name: 'order_confirm', requirements: ['orderId' => '\\d+'], methods: ['POST'])]
    public function confirmOrder(
        int $orderId,
        Request $request,
        VendorRepository $vendorRepo,
        VendorTransactionRepository $txRepo,
        EntityManagerInterface $em,
    ): Response {
        $vendor = $this->getMyVendor($vendorRepo);

        if (!$this->isCsrfTokenValid('vendor_confirm_' . $orderId, $request->request->get('_token'))) {
            $this->addFlash('error', 'Noto\'g\'ri so\'rov.');
            return $this->redirectToRoute('vendor_orders');
        }

        $order = $em->find(Order::class, $orderId);
        if (!$order) {
            throw $this->createNotFoundException();
        }

        // Takroriy transaksiyaning oldini olish
        $exists = $txRepo->findOneBy(['vendor' => $vendor, 'order' => $order]);
        if ($exists) {
            $this->addFlash('warning', 'Bu buyurtma allaqachon tasdiqlangan.');
            return $this->redirectToRoute('vendor_orders');
        }

        // saleAmount = shu vendor uchun bu orderdagi barcha item lineTotallar yig'indisi
        $items = $em->createQuery(
            'SELECT oi FROM App\Entity\OrderItem oi WHERE oi.order = :order AND oi.vendor = :vendor'
        )->setParameter('order', $order)->setParameter('vendor', $vendor)->getResult();

        $saleAmount = array_reduce($items, fn($sum, $oi) => bcadd($sum, (string)$oi->getLineTotal(), 2), '0.00');

        if ((float)$saleAmount <= 0) {
            $this->addFlash('error', 'Buyurtma summasini hisoblab bo\'lmadi.');
            return $this->redirectToRoute('vendor_orders');
        }

        $rate       = (float)$vendor->getCommissionRate();
        $commission = round((float)$saleAmount * $rate / 100, 2);

        $tx = new VendorTransaction();
        $tx->setVendor($vendor);
        $tx->setOrder($order);
        $tx->setSaleAmount($saleAmount);
        $tx->setCommissionRate((string)$rate);
        $tx->setCommissionAmount((string)$commission);

        $vendor->addToEarnings($saleAmount);

        // Do'konchi yetkazib bergach, buyurtma admin "kutilmoqda" ro'yxatidan chiqishi kerak.
        $order->setPaymentStatus(Order::PAYMENT_STATUS_APPROVED);
        $order->setOrderStatus(Order::ORDER_STATUS_COMPLETED);
        $order->setApprovedAt(new \DateTimeImmutable());
        $order->setAdminNote('Do\'konchi tomonidan yetkazib berildi');
        $order->touch();

        $em->persist($tx);
        $em->flush();

        $this->addFlash('success', 'Yetkazib berish tasdiqlandi! ' . number_format((float)$saleAmount, 0, '.', ' ') . ' so\'m kassaga yozildi.');
        return $this->redirectToRoute('vendor_orders');
    }

    #[Route('/orders/{orderId}/cancel', name: 'order_cancel', requirements: ['orderId' => '\\d+'], methods: ['POST'])]
    public function cancelOrder(
        int $orderId,
        Request $request,
        VendorRepository $vendorRepo,
        VendorTransactionRepository $txRepo,
        EntityManagerInterface $em,
    ): Response {
        $vendor = $this->getMyVendor($vendorRepo);

        if (!$this->isCsrfTokenValid('vendor_cancel_' . $orderId, $request->request->get('_token'))) {
            $this->addFlash('error', 'Noto\'g\'ri so\'rov.');
            return $this->redirectToRoute('vendor_orders');
        }

        $order = $em->find(Order::class, $orderId);
        if (!$order) {
            throw $this->createNotFoundException();
        }

        // Buyurtma do'konchiga tegishli ekanini tekshiramiz
        $myItemsCount = (int)$em->createQuery(
            'SELECT COUNT(oi.id) FROM App\Entity\OrderItem oi WHERE oi.order = :order AND oi.vendor = :vendor'
        )->setParameter('order', $order)->setParameter('vendor', $vendor)->getSingleScalarResult();

        if ($myItemsCount === 0) {
            $this->addFlash('error', 'Siz bu buyurtmani bekor qila olmaysiz.');
            return $this->redirectToRoute('vendor_orders');
        }

        // Agar boshqa do'kon mahsuloti ham bo'lsa, bu yerda bekor qilishga ruxsat bermaymiz
        $otherItemsCount = (int)$em->createQuery(
            'SELECT COUNT(oi.id) FROM App\Entity\OrderItem oi WHERE oi.order = :order AND oi.vendor != :vendor'
        )->setParameter('order', $order)->setParameter('vendor', $vendor)->getSingleScalarResult();

        if ($otherItemsCount > 0) {
            $this->addFlash('warning', 'Bu buyurtmada boshqa do\'kon mahsulotlari ham bor. Bekor qilish uchun admin bilan bog\'laning.');
            return $this->redirectToRoute('vendor_orders');
        }

        // Allaqachon yetkazib berilgan bo'lsa bekor qilib bo'lmaydi
        $exists = $txRepo->findOneBy(['vendor' => $vendor, 'order' => $order]);
        if ($exists) {
            $this->addFlash('warning', 'Bu buyurtma allaqachon tasdiqlangan. Bekor qilib bo\'lmaydi.');
            return $this->redirectToRoute('vendor_orders');
        }

        $order->setOrderStatus(Order::ORDER_STATUS_CANCELED);
        $order->setPaymentStatus(Order::PAYMENT_STATUS_REJECTED);
        $order->setApprovedAt(new \DateTimeImmutable());
        $order->setAdminNote('Do\'konchi tomonidan bekor qilindi');
        $order->touch();

        $em->flush();

        $this->addFlash('success', 'Buyurtma bekor qilindi.');
        return $this->redirectToRoute('vendor_orders');
    }

    // ─── Virtual Kassa ───────────────────────────────────────────────────────

    #[Route('/wallet', name: 'wallet')]
    public function wallet(
        VendorRepository $vendorRepo,
        VendorTransactionRepository $txRepo,
    ): Response {
        $vendor = $this->getMyVendor($vendorRepo);
        $transactions = $txRepo->findBy(['vendor' => $vendor], ['createdAt' => 'DESC']);
        $unsettled = $txRepo->sumUnsettledCommission($vendor);

        return $this->render('vendor/wallet.html.twig', [
            'vendor'       => $vendor,
            'transactions' => $transactions,
            'unsettled'    => $unsettled,
        ]);
    }
}
