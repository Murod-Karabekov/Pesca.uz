<?php

namespace App\Repository;

use App\Entity\UserMembership;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserMembership>
 */
class UserMembershipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserMembership::class);
    }

    public function findActiveByUser(User $user): ?UserMembership
    {
        return $this->createQueryBuilder('um')
            ->where('um.user = :user')
            ->andWhere('um.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'active')
            ->orderBy('um.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return UserMembership[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('um')
            ->where('um.user = :user')
            ->setParameter('user', $user)
            ->orderBy('um.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
