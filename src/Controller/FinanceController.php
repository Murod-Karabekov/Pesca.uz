<?php

namespace App\Controller;

use App\Entity\Withdrawal;
use App\Entity\WalletTopupRequest;
use App\Repository\WalletTopupRequestRepository;
use App\Service\BonusService;
use App\Service\MembershipService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/moliya')]
#[IsGranted('ROLE_USER')]
class FinanceController extends AbstractController
{
    /**
     * Moliya bo'limi — asosiy sahifa
     */
    #[Route('', name: 'app_finance_index')]
    public function index(
        BonusService $bonusService,
        MembershipService $membershipService,
        WalletTopupRequestRepository $topupRequestRepository,
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $plan = $membershipService->getUserPlan($user);

        // Faqat tarif a'zolari uchun
        if (!$plan->isHasFinanceSection()) {
            $this->addFlash('warning', 'Moliya bo\'limi faqat tarif a\'zolari uchun. Tarifga a\'zo bo\'ling!');
            return $this->redirectToRoute('app_hamkorlik_index');
        }

        $stats = $bonusService->getFinanceStats($user);

        return $this->render('finance/index.html.twig', [
            'plan' => $plan,
            'stats' => $stats,
            'user' => $user,
            'topupRequests' => array_slice($topupRequestRepository->findByUser($user), 0, 5),
        ]);
    }

    /**
     * Umumiy hamyonni to'ldirish so'rovi
     */
    #[Route('/topup', name: 'app_finance_topup', methods: ['GET', 'POST'])]
    public function topup(
        Request $request,
        EntityManagerInterface $em,
        MembershipService $membershipService,
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $plan = $membershipService->getUserPlan($user);

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('wallet_topup', $request->request->get('_token'))) {
                $this->addFlash('error', 'Xavfsizlik xatosi.');
                return $this->redirectToRoute('app_finance_topup');
            }

            $amount = trim($request->request->get('amount', '0'));

            if (!is_numeric($amount) || bccomp($amount, WalletTopupRequest::MIN_AMOUNT, 2) < 0) {
                $this->addFlash('error', 'Noto\'g\'ri summa. Minimal summa: 50 000 so\'m.');
                return $this->redirectToRoute('app_finance_topup');
            }

            $requestEntity = new WalletTopupRequest();
            $requestEntity->setUser($user);
            $requestEntity->setAmount(number_format((float) $amount, 2, '.', ''));
            $requestEntity->setPaymentMethod('card');
            $requestEntity->setPayerName((string) $user->getFullName());
            $requestEntity->setPayerPhone((string) $user->getPhone());
            $requestEntity->setPaymentReference(null);
            $requestEntity->setComment(null);

            $em->persist($requestEntity);
            $em->flush();

            $this->addFlash('success', 'Hamyonni to\'ldirish so\'rovi yuborildi. Admin tasdiqlashi kutilmoqda.');
            return $this->redirectToRoute('app_profile_index');
        }

        return $this->render('finance/topup.html.twig', [
            'plan' => $plan,
            'minAmount' => WalletTopupRequest::MIN_AMOUNT,
        ]);
    }

    /**
     * Foydalanuvchi top-up so'rovlari tarixi
     */
    #[Route('/topup/history', name: 'app_finance_topup_history', methods: ['GET'])]
    public function topupHistory(
        MembershipService $membershipService,
        WalletTopupRequestRepository $topupRequestRepository,
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $plan = $membershipService->getUserPlan($user);

        return $this->render('finance/topup_history.html.twig', [
            'plan' => $plan,
            'requests' => $topupRequestRepository->findByUser($user),
        ]);
    }

    /**
     * Referral link va jamoam
     */
    #[Route('/referral', name: 'app_finance_referral')]
    public function referral(BonusService $bonusService, MembershipService $membershipService): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $plan = $membershipService->getUserPlan($user);
        $stats = $bonusService->getFinanceStats($user);

        return $this->render('finance/referral.html.twig', [
            'plan' => $plan,
            'stats' => $stats,
            'user' => $user,
        ]);
    }

    /**
     * Naqd yechish so'rovi (faqat VIP)
     */
    #[Route('/withdraw', name: 'app_finance_withdraw', methods: ['GET', 'POST'])]
    public function withdraw(
        Request $request,
        BonusService $bonusService,
        MembershipService $membershipService,
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $plan = $membershipService->getUserPlan($user);

        // Faqat VIP uchun
        if (!$user->isVip()) {
            $this->addFlash('error', 'Naqd yechish faqat VIP a\'zolar uchun.');
            return $this->redirectToRoute('app_finance_index');
        }

        $balance = $bonusService->getBalance($user);
        $minAmount = Withdrawal::MIN_AMOUNT;

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('withdraw', $request->request->get('_token'))) {
                $this->addFlash('error', 'Xavfsizlik xatosi.');
                return $this->redirectToRoute('app_finance_withdraw');
            }

            $amount = $request->request->get('amount', '0');
            $cardNumber = preg_replace('/\s+/', '', $request->request->get('card_number', ''));
            $cardHolderName = trim($request->request->get('card_holder_name', ''));

            if (!preg_match('/^\d{16}$/', $cardNumber)) {
                $this->addFlash('error', 'Karta raqami 16 ta raqamdan iborat bo\'lishi kerak.');
                return $this->redirectToRoute('app_finance_withdraw');
            }

            if (strlen($cardHolderName) < 3) {
                $this->addFlash('error', 'Karta egasining ismini kiriting.');
                return $this->redirectToRoute('app_finance_withdraw');
            }

            try {
                $bonusService->requestWithdrawal($user, $amount, $cardNumber, $cardHolderName);
                $this->addFlash('success', 'Yechish so\'rovi qabul qilindi! Admin tasdiqlashini kuting.');
                return $this->redirectToRoute('app_finance_index');
            } catch (\LogicException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('finance/withdraw.html.twig', [
            'balance' => $balance,
            'minAmount' => $minAmount,
            'plan' => $plan,
        ]);
    }
}
