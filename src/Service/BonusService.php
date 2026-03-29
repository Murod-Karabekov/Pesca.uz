<?php

namespace App\Service;

use App\Entity\BonusTransaction;
use App\Entity\BonusWallet;
use App\Entity\User;
use App\Entity\Withdrawal;
use App\Repository\BonusTransactionRepository;
use App\Repository\BonusWalletRepository;
use App\Repository\ReferralLinkRepository;
use App\Repository\WithdrawalRepository;
use Doctrine\ORM\EntityManagerInterface;

class BonusService
{
    public function __construct(
        private EntityManagerInterface $em,
        private BonusWalletRepository $walletRepo,
        private BonusTransactionRepository $transactionRepo,
        private ReferralLinkRepository $referralLinkRepo,
        private WithdrawalRepository $withdrawalRepo,
    ) {
    }

    /**
     * Foydalanuvchining bonus balansini olish
     */
    public function getBalance(User $user): string
    {
        $wallet = $this->walletRepo->findByUser($user);

        return $wallet?->getBalance() ?? '0.00';
    }

    /**
     * Wallet'ni olish yoki yaratish
     */
    public function getOrCreateWallet(User $user): BonusWallet
    {
        return $this->walletRepo->findOrCreateByUser($user);
    }

    /**
     * Tovar olishda bonus ishlatish
     */
    public function spendBonus(User $user, string $amount, ?int $orderId = null): void
    {
        $wallet = $this->walletRepo->findOrCreateByUser($user);

        if (bccomp($wallet->getBalance(), $amount, 2) < 0) {
            throw new \LogicException('Bonus balansda yetarli mablag\' yo\'q');
        }

        $wallet->debit($amount);

        $transaction = new BonusTransaction();
        $transaction->setUser($user);
        $transaction->setType(BonusTransaction::TYPE_SPENT);
        $transaction->setAmount('-' . $amount);
        $transaction->setDescription('Tovar olishda ishlatildi');
        $transaction->setSourceOrderId($orderId);

        $this->em->persist($transaction);
        $this->em->flush();
    }

    /**
     * Naqd yechish so'rovi (faqat VIP)
     */
    public function requestWithdrawal(User $user, string $amount, string $cardNumber, string $cardHolderName): Withdrawal
    {
        // Faqat VIP yecha oladi
        if (!$user->isVip()) {
            throw new \LogicException('Naqd yechish faqat VIP a\'zolar uchun');
        }

        // Minimum summa tekshirish
        if (bccomp($amount, Withdrawal::MIN_AMOUNT, 2) < 0) {
            throw new \LogicException(sprintf(
                'Minimal yechish summasi: %s so\'m',
                number_format((float) Withdrawal::MIN_AMOUNT, 0, '.', ' ')
            ));
        }

        $wallet = $this->walletRepo->findOrCreateByUser($user);

        if (bccomp($wallet->getBalance(), $amount, 2) < 0) {
            throw new \LogicException('Bonus balansda yetarli mablag\' yo\'q');
        }

        // Balansdan ayirish
        $wallet->withdraw($amount);

        // Withdrawal so'rov yaratish
        $withdrawal = new Withdrawal();
        $withdrawal->setUser($user);
        $withdrawal->setAmount($amount);
        $withdrawal->setCardNumber($cardNumber);
        $withdrawal->setCardHolderName($cardHolderName);
        $withdrawal->setMethod('card');

        $this->em->persist($withdrawal);
        $this->em->flush();

        return $withdrawal;
    }

    /**
     * Foydalanuvchining moliya statistikasini olish
     */
    public function getFinanceStats(User $user): array
    {
        $wallet = $this->walletRepo->findByUser($user);
        $referralLinks = $this->referralLinkRepo->findByReferrer($user);

        // Referallarni plan bo'yicha guruhlash
        $referralsByPlan = [];
        foreach ($referralLinks as $link) {
            $plan = $link->getReferrerPlanAtTime();
            if (!isset($referralsByPlan[$plan])) {
                $referralsByPlan[$plan] = [
                    'count' => 0,
                    'productPercent' => $link->getProductPercent(),
                    'planPercent' => $link->getPlanPercent(),
                ];
            }
            $referralsByPlan[$plan]['count']++;
        }

        return [
            'balance' => $wallet?->getBalance() ?? '0.00',
            'totalEarned' => $wallet?->getTotalEarned() ?? '0.00',
            'totalSpent' => $wallet?->getTotalSpent() ?? '0.00',
            'totalWithdrawn' => $wallet?->getTotalWithdrawn() ?? '0.00',
            'productEarned' => $this->transactionRepo->getTotalProductReferralByUser($user),
            'planEarned' => $this->transactionRepo->getTotalPlanReferralByUser($user),
            'totalReferrals' => count($referralLinks),
            'referralsByPlan' => $referralsByPlan,
            'transactions' => $this->transactionRepo->findByUser($user),
            'referralLinks' => $referralLinks,
        ];
    }
}
