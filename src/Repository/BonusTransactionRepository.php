<?php

namespace App\Repository;

use App\Entity\BonusTransaction;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BonusTransaction>
 */
class BonusTransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BonusTransaction::class);
    }

    /**
     * @return BonusTransaction[]
     */
    public function findByUser(User $user, int $limit = 50): array
    {
        return $this->createQueryBuilder('bt')
            ->where('bt.user = :user')
            ->setParameter('user', $user)
            ->orderBy('bt.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Jami ishlab topilgan summa (type = product_referral yoki plan_referral)
     */
    public function getTotalEarnedByUser(User $user): string
    {
        $result = $this->createQueryBuilder('bt')
            ->select('SUM(bt.amount)')
            ->where('bt.user = :user')
            ->andWhere('bt.type IN (:types)')
            ->setParameter('user', $user)
            ->setParameter('types', [
                BonusTransaction::TYPE_PRODUCT_REFERRAL,
                BonusTransaction::TYPE_PLAN_REFERRAL,
            ])
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? '0.00';
    }

    /**
     * Mahsulotdan ishlab topilgan
     */
    public function getTotalProductReferralByUser(User $user): string
    {
        $result = $this->createQueryBuilder('bt')
            ->select('SUM(bt.amount)')
            ->where('bt.user = :user')
            ->andWhere('bt.type = :type')
            ->setParameter('user', $user)
            ->setParameter('type', BonusTransaction::TYPE_PRODUCT_REFERRAL)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? '0.00';
    }

    public function hasProductReferralForOrder(int $orderId): bool
    {
        $count = (int) $this->createQueryBuilder('bt')
            ->select('COUNT(bt.id)')
            ->where('bt.type = :type')
            ->andWhere('bt.sourceOrderId = :orderId')
            ->setParameter('type', BonusTransaction::TYPE_PRODUCT_REFERRAL)
            ->setParameter('orderId', $orderId)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Tarifdan ishlab topilgan
     */
    public function getTotalPlanReferralByUser(User $user): string
    {
        $result = $this->createQueryBuilder('bt')
            ->select('SUM(bt.amount)')
            ->where('bt.user = :user')
            ->andWhere('bt.type = :type')
            ->setParameter('user', $user)
            ->setParameter('type', BonusTransaction::TYPE_PLAN_REFERRAL)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? '0.00';
    }
}
