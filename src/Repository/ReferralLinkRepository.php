<?php

namespace App\Repository;

use App\Entity\ReferralLink;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReferralLink>
 */
class ReferralLinkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReferralLink::class);
    }

    /**
     * Berilgan user uchun referral link topish (uni kim olib kelgan?)
     */
    public function findByReferred(User $referred): ?ReferralLink
    {
        return $this->createQueryBuilder('rl')
            ->where('rl.referred = :referred')
            ->andWhere('rl.isActive = true')
            ->setParameter('referred', $referred)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Referrer'ning barcha referallari
     * @return ReferralLink[]
     */
    public function findByReferrer(User $referrer): array
    {
        return $this->createQueryBuilder('rl')
            ->where('rl.referrer = :referrer')
            ->andWhere('rl.isActive = true')
            ->setParameter('referrer', $referrer)
            ->orderBy('rl.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Referrer'ning referallari soni
     */
    public function countByReferrer(User $referrer): int
    {
        return (int) $this->createQueryBuilder('rl')
            ->select('COUNT(rl.id)')
            ->where('rl.referrer = :referrer')
            ->andWhere('rl.isActive = true')
            ->setParameter('referrer', $referrer)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Referrer'ning ma'lum plandagi referallari
     * @return ReferralLink[]
     */
    public function findByReferrerAndPlan(User $referrer, string $planSlug): array
    {
        return $this->createQueryBuilder('rl')
            ->where('rl.referrer = :referrer')
            ->andWhere('rl.referrerPlanAtTime = :plan')
            ->andWhere('rl.isActive = true')
            ->setParameter('referrer', $referrer)
            ->setParameter('plan', $planSlug)
            ->orderBy('rl.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
