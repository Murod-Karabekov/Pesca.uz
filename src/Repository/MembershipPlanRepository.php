<?php

namespace App\Repository;

use App\Entity\MembershipPlan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MembershipPlan>
 */
class MembershipPlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MembershipPlan::class);
    }

    /**
     * @return MembershipPlan[]
     */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('mp')
            ->where('mp.isActive = true')
            ->orderBy('mp.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Faqat pullik tariflar
     * @return MembershipPlan[]
     */
    public function findPaidPlans(): array
    {
        return $this->createQueryBuilder('mp')
            ->where('mp.isActive = true')
            ->andWhere('mp.slug != :free')
            ->setParameter('free', MembershipPlan::SLUG_FREE)
            ->orderBy('mp.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findBySlug(string $slug): ?MembershipPlan
    {
        return $this->findOneBy(['slug' => $slug]);
    }
}
