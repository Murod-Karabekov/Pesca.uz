<?php

namespace App\Service;

use App\Entity\BonusTransaction;
use App\Entity\BonusWallet;
use App\Entity\MembershipPlan;
use App\Entity\ReferralLink;
use App\Entity\User;
use App\Entity\UserMembership;
use App\Repository\BonusWalletRepository;
use App\Repository\MembershipPlanRepository;
use App\Repository\ReferralLinkRepository;
use Doctrine\ORM\EntityManagerInterface;

class MembershipService
{
    public function __construct(
        private EntityManagerInterface $em,
        private MembershipPlanRepository $planRepo,
        private ReferralLinkRepository $referralLinkRepo,
        private BonusWalletRepository $walletRepo,
    ) {
    }

    /**
     * Foydalanuvchini tarifga a'zo qilish
     */
    public function subscribeToPlan(User $user, MembershipPlan $plan, string $paymentMethod = 'manual'): UserMembership
    {
        $price = $plan->getPrice();

        // Umumiy balansdan to'lovni yechish
        if (bccomp($price, '0', 2) > 0) {
            if (bccomp($user->getGeneralBalance(), $price, 2) < 0) {
                throw new \LogicException('Umumiy balansda yetarli mablag\' yo\'q. Kerakli summa: ' . $price . ' so\'m');
            }
            $user->deductGeneralBalance($price);

            // To'lov tranzaksiyasini yozish
            $paymentTx = new BonusTransaction();
            $paymentTx->setUser($user);
            $paymentTx->setType(BonusTransaction::TYPE_PLAN_PAYMENT);
            $paymentTx->setAmount($price);
            $paymentTx->setDescription(sprintf('%s tarifiga to\'lov', $plan->getName()));
            $this->em->persist($paymentTx);
        }

        // 1. Membership yozuvini yaratish
        $membership = new UserMembership();
        $membership->setUser($user);
        $membership->setPlan($plan);
        $membership->setPaidAmount($plan->getPrice());
        $membership->setPaymentMethod($paymentMethod);

        $this->em->persist($membership);

        // 2. User planini yangilash
        $user->setCurrentPlan($plan->getSlug());

        // 3. Referral bonus hisoblash (tarif sotuvdan)
        $this->processReferralFromPlanSale($user, $membership);

        $this->em->flush();

        return $membership;
    }

    /**
     * Tarif sotilganda referrer'ga bonus berish
     */
    private function processReferralFromPlanSale(User $buyer, UserMembership $membership): void
    {
        // Buyer'ni kim olib kelgan?
        $referralLink = $this->referralLinkRepo->findByReferred($buyer);

        if ($referralLink === null) {
            return;
        }

        // ❄️ Muzlatilgan plan foizini ishlatamiz
        $planPercent = $referralLink->getPlanPercent();

        if (bccomp($planPercent, '0', 2) <= 0) {
            return; // free user'ning tarif foizi 0%
        }

        $bonusAmount = bcmul($membership->getPaidAmount(), bcdiv($planPercent, '100', 4), 2);

        if (bccomp($bonusAmount, '0', 2) <= 0) {
            return;
        }

        $referrer = $referralLink->getReferrer();

        // Wallet topish yoki yaratish
        $wallet = $this->walletRepo->findOrCreateByUser($referrer);
        $wallet->credit($bonusAmount);

        // Tranzaksiya yozish
        $transaction = new BonusTransaction();
        $transaction->setUser($referrer);
        $transaction->setType(BonusTransaction::TYPE_PLAN_REFERRAL);
        $transaction->setAmount($bonusAmount);
        $transaction->setDescription(sprintf(
            '%s tarif sotuvdan %s%% bonus',
            $buyer->getFullName(),
            $planPercent
        ));
        $transaction->setSourceUser($buyer);
        $transaction->setSourceMembershipId($membership->getId());
        $transaction->setAppliedPercent($planPercent);
        $transaction->setReferrerPlanAtTime($referralLink->getReferrerPlanAtTime());

        $this->em->persist($transaction);
    }

    /**
     * Foydalanuvchining hozirgi plan ma'lumotlarini olish
     */
    public function getUserPlan(User $user): MembershipPlan
    {
        $plan = $this->planRepo->findBySlug($user->getCurrentPlan());

        if ($plan === null) {
            $plan = $this->planRepo->findBySlug(MembershipPlan::SLUG_FREE);
        }

        return $plan;
    }

    /**
     * Upgrade imkoniyatini tekshirish
     */
    public function canUpgradeTo(User $user, MembershipPlan $targetPlan): bool
    {
        $hierarchy = [
            MembershipPlan::SLUG_FREE => 0,
            MembershipPlan::SLUG_START => 1,
            MembershipPlan::SLUG_PREMIUM => 2,
            MembershipPlan::SLUG_VIP => 3,
        ];

        $currentLevel = $hierarchy[$user->getCurrentPlan()] ?? 0;
        $targetLevel = $hierarchy[$targetPlan->getSlug()] ?? 0;

        return $targetLevel > $currentLevel;
    }
}
