<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Entity\Vendor;
use App\Entity\VendorTransaction;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use App\Repository\VendorRepository;
use App\Repository\VendorTransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/vendors', name: 'admin_vendor_')]
#[IsGranted('ROLE_ADMIN')]
class VendorManagementController extends AbstractController
{
    // ─── Do'konlar ro'yxati ──────────────────────────────────────────────────

    #[Route('', name: 'index')]
    public function index(VendorRepository $vendorRepo): Response
    {
        $vendors = $vendorRepo->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/vendor/index.html.twig', [
            'vendors' => $vendors,
        ]);
    }

    // ─── Yangi do'kon qo'shish ───────────────────────────────────────────────

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        UserRepository $userRepo,
        EntityManagerInterface $em,
    ): Response {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('vendor_new', $request->request->get('_token'))) {
                $this->addFlash('error', 'Noto\'g\'ri CSRF token.');
                return $this->redirectToRoute('admin_vendor_new');
            }

            $name           = trim($request->request->get('name', ''));
            $phone          = trim($request->request->get('phone', ''));
            $address        = trim($request->request->get('address', ''));
            $commissionRate = trim($request->request->get('commission_rate', '10'));
            $ownerPhone     = trim($request->request->get('owner_phone', ''));

            if (!$name) {
                $this->addFlash('error', 'Do\'kon nomini kiriting.');
                return $this->redirectToRoute('admin_vendor_new');
            }

            $vendor = new Vendor();
            $vendor->setName($name);
            $vendor->setPhone($phone ?: null);
            $vendor->setAddress($address ?: null);
            $vendor->setCommissionRate(is_numeric($commissionRate) ? $commissionRate : '10.00');

            if ($ownerPhone) {
                $owner = $userRepo->findOneBy(['phone' => $ownerPhone]);
                if ($owner) {
                    $vendor->setOwner($owner);
                    // Do'konchiga ROLE_VENDOR berish
                    $roles = $owner->getRoles();
                    if (!in_array('ROLE_VENDOR', $roles, true)) {
                        $roles[] = 'ROLE_VENDOR';
                        $owner->setRoles(array_values(array_unique($roles)));
                    }
                } else {
                    $this->addFlash('warning', 'Foydalanuvchi topilmadi: ' . $ownerPhone . '. Do\'kon yaratildi, ammo egasi biriktirilmadi.');
                }
            }

            $em->persist($vendor);
            $em->flush();

            $this->addFlash('success', '"' . $vendor->getName() . '" do\'koni yaratildi.');
            return $this->redirectToRoute('admin_vendor_show', ['id' => $vendor->getId()]);
        }

        return $this->render('admin/vendor/new.html.twig');
    }

    // ─── Do'kon detali va statistika ────────────────────────────────────────

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'])]
    public function show(
        Vendor $vendor,
        ProductRepository $productRepo,
        VendorTransactionRepository $txRepo,
    ): Response {
        $pendingProducts   = $productRepo->findBy(['vendor' => $vendor, 'publishStatus' => Product::PUBLISH_STATUS_PENDING]);
        $publishedProducts = $productRepo->findBy(['vendor' => $vendor, 'publishStatus' => Product::PUBLISH_STATUS_PUBLISHED]);
        $transactions      = $txRepo->findBy(['vendor' => $vendor], ['createdAt' => 'DESC'], 20);
        $unsettled         = $txRepo->sumUnsettledCommission($vendor);
        $totalSales        = array_sum(array_map(fn($t) => (float)$t->getSaleAmount(), $transactions));

        return $this->render('admin/vendor/show.html.twig', [
            'vendor'            => $vendor,
            'pendingProducts'   => $pendingProducts,
            'publishedProducts' => $publishedProducts,
            'transactions'      => $transactions,
            'unsettled'         => $unsettled,
            'totalSales'        => $totalSales,
        ]);
    }

    // ─── Do'konni tahrirlash ─────────────────────────────────────────────────

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Vendor $vendor,
        Request $request,
        UserRepository $userRepo,
        EntityManagerInterface $em,
    ): Response {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('vendor_edit_' . $vendor->getId(), $request->request->get('_token'))) {
                $this->addFlash('error', 'Noto\'g\'ri CSRF token.');
                return $this->redirectToRoute('admin_vendor_edit', ['id' => $vendor->getId()]);
            }

            $vendor->setName(trim($request->request->get('name', $vendor->getName())));
            $vendor->setPhone(trim($request->request->get('phone', '')) ?: null);
            $vendor->setAddress(trim($request->request->get('address', '')) ?: null);
            $cr = trim($request->request->get('commission_rate', ''));
            if (is_numeric($cr)) {
                $vendor->setCommissionRate($cr);
            }
            $vendor->setIsActive((bool)$request->request->get('is_active', true));

            // Egani o'zgartirish
            $ownerPhone = trim($request->request->get('owner_phone', ''));
            if ($ownerPhone) {
                $owner = $userRepo->findOneBy(['phone' => $ownerPhone]);
                if ($owner) {
                    $vendor->setOwner($owner);
                    $roles = $owner->getRoles();
                    if (!in_array('ROLE_VENDOR', $roles, true)) {
                        $roles[] = 'ROLE_VENDOR';
                        $owner->setRoles(array_values(array_unique($roles)));
                    }
                } else {
                    $this->addFlash('warning', 'Foydalanuvchi topilmadi: ' . $ownerPhone);
                }
            }

            $em->flush();
            $this->addFlash('success', 'Do\'kon ma\'lumotlari yangilandi.');
            return $this->redirectToRoute('admin_vendor_show', ['id' => $vendor->getId()]);
        }

        return $this->render('admin/vendor/edit.html.twig', ['vendor' => $vendor]);
    }

    // ─── Mahsulotni tasdiqlash (Pending → Published + SmartStyle) ───────────

    #[Route('/product/{id}/approve', name: 'product_approve', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function approveProduct(
        Product $product,
        Request $request,
        CategoryRepository $categoryRepo,
        EntityManagerInterface $em,
    ): Response {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('approve_product_' . $product->getId(), $request->request->get('_token'))) {
                $this->addFlash('error', 'Noto\'g\'ri CSRF token.');
                return $this->redirectToRoute('admin_vendor_product_approve', ['id' => $product->getId()]);
            }

            $action = $request->request->get('action'); // 'approve' | 'reject'

            if ($action === 'approve') {
                // SmartStyle sozlamalari
                $gender      = $request->request->get('gender');
                $skinTones   = $request->request->all('skin_tones');
                $faceShapes  = $request->request->all('face_shapes');
                $occasions   = $request->request->all('occasions');
                $styleIntents= $request->request->all('style_intents');
                $seasons     = $request->request->all('seasons');
                $bodyTypes   = $request->request->all('body_types');

                $product->setGender($gender ?: null);
                $product->setSkinTones($skinTones ?: null);
                $product->setFaceShapes($faceShapes ?: null);
                $product->setOccasions($occasions ?: null);
                $product->setStyleIntents($styleIntents ?: null);
                $product->setSeasons($seasons ?: null);
                $product->setBodyTypes($bodyTypes ?: null);
                $product->setPublishStatus(Product::PUBLISH_STATUS_PUBLISHED);
                $product->setStatus(true);

                $this->addFlash('success', '"' . $product->getName() . '" tasdiqlandi va saytda ko\'rinadi.');
            } elseif ($action === 'reject') {
                $product->setPublishStatus(Product::PUBLISH_STATUS_REJECTED);
                $product->setStatus(false);
                $this->addFlash('warning', '"' . $product->getName() . '" rad etildi.');
            }

            $em->flush();

            $vendorId = $product->getVendor()?->getId();
            if ($vendorId) {
                return $this->redirectToRoute('admin_vendor_show', ['id' => $vendorId]);
            }
            return $this->redirectToRoute('admin_vendor_index');
        }

        $categories = $categoryRepo->findBy([], ['name' => 'ASC']);

        return $this->render('admin/vendor/product_approve.html.twig', [
            'product'    => $product,
            'categories' => $categories,
        ]);
    }

    // ─── Komissiyani "settled" qilish ───────────────────────────────────────

    #[Route('/{id}/settle', name: 'settle', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function settle(
        Vendor $vendor,
        Request $request,
        VendorTransactionRepository $txRepo,
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('vendor_settle_' . $vendor->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Noto\'g\'ri CSRF token.');
            return $this->redirectToRoute('admin_vendor_show', ['id' => $vendor->getId()]);
        }

        $unsettled = $txRepo->findBy(['vendor' => $vendor, 'status' => VendorTransaction::STATUS_CONFIRMED]);
        $now = new \DateTimeImmutable();
        $total = 0;
        foreach ($unsettled as $tx) {
            $tx->setStatus(VendorTransaction::STATUS_SETTLED);
            $tx->setSettledAt($now);
            $total += (float)$tx->getCommissionAmount();
        }

        $em->flush();
        $this->addFlash('success', number_format($total, 0, '.', ' ') . ' so\'m komissiya "settled" qilindi.');
        return $this->redirectToRoute('admin_vendor_show', ['id' => $vendor->getId()]);
    }
}
