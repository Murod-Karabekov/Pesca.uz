<?php

namespace App\Service;

use App\Entity\BonusTransaction;
use App\Entity\MembershipPlan;
use App\Entity\ReferralLink;
use App\Entity\User;
use App\Repository\BonusWalletRepository;
use App\Repository\MembershipPlanRepository;
use App\Repository\ReferralLinkRepository;
use Doctrine\ORM\EntityManagerInterface;

class ReferralService
{
    public function __construct(
        private EntityManagerInterface $em,
        private ReferralLinkRepository $referralLinkRepo,
        private MembershipPlanRepository $planRepo,
        private BonusWalletRepository $walletRepo,
    ) {
    }

    /**
     * Yangi foydalanuvchi ro'yxatdan o'tganda referral bog'lanishni yaratish
     * ❄️ Referrer'ning hozirgi foizlari muzlatiladi
     */
    public function registerReferral(User $referrer, User $newUser): ReferralLink
    {
        $currentPlan = $this->planRepo->findBySlug($referrer->getCurrentPlan());

        if ($currentPlan === null) {
            $currentPlan = $this->planRepo->findBySlug(MembershipPlan::SLUG_FREE);
        }

        $referralLink = new ReferralLink();
        $referralLink->setReferrer($referrer);
        $referralLink->setReferred($newUser);
        $referralLink->setReferrerPlanAtTime($currentPlan->getSlug());
        $referralLink->setProductPercent($currentPlan->getProductReferralPercent());
        $referralLink->setPlanPercent($currentPlan->getPlanReferralPercent());

        // User'ga referrer'ni saqlash
        $newUser->setReferredBy($referrer);

        $this->em->persist($referralLink);
        $this->em->flush();

        return $referralLink;
    }

    /**
     * Mahsulot sotilganda referral bonus hisoblash
     * ❄️ Muzlatilgan foizlar ishlatiladi
     *
     * @param string $orderTotal — buyurtma summasi
     * @param int|null $orderId — buyurtma ID si
     */
    public function processProductReferral(User $buyer, string $orderTotal, ?int $orderId = null): void
    {
        $referralLink = $this->referralLinkRepo->findByReferred($buyer);

        if ($referralLink === null) {
            return;
        }

        $productPercent = $referralLink->getProductPercent();

        if (bccomp($productPercent, '0', 2) <= 0) {
            return;
        }

        $bonusAmount = bcmul($orderTotal, bcdiv($productPercent, '100', 4), 2);

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
        $transaction->setType(BonusTransaction::TYPE_PRODUCT_REFERRAL);
        $transaction->setAmount($bonusAmount);
        $transaction->setDescription(sprintf(
            '%s mahsulot sotuvdan %s%% bonus',
            $buyer->getFullName(),
            $productPercent
        ));
        $transaction->setSourceUser($buyer);
        $transaction->setSourceOrderId($orderId);
        $transaction->setAppliedPercent($productPercent);
        $transaction->setReferrerPlanAtTime($referralLink->getReferrerPlanAtTime());

        $this->em->persist($transaction);
        $this->em->flush();
    }

    /**
     * Referral code orqali referrer'ni topish
     */
    public function findReferrerByCode(string $code): ?User
    {
        return $this->em->getRepository(User::class)->findOneBy(['referralCode' => $code]);
    }
}
