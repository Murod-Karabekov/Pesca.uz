<?php

namespace App\Repository;

use App\Entity\Withdrawal;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Withdrawal>
 */
class WithdrawalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Withdrawal::class);
    }

    /**
     * @return Withdrawal[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.user = :user')
            ->setParameter('user', $user)
            ->orderBy('w.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Kutilayotgan (pending) so'rovlar
     * @return Withdrawal[]
     */
    public function findPending(): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.status = :status')
            ->setParameter('status', Withdrawal::STATUS_PENDING)
            ->orderBy('w.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Withdrawal[]
     */
    public function findAll(): array
    {
        return $this->createQueryBuilder('w')
            ->orderBy('w.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
