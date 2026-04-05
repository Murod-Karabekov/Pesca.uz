<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\WalletTopupRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WalletTopupRequest>
 */
class WalletTopupRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WalletTopupRequest::class);
    }

    /**
     * @return WalletTopupRequest[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->setParameter('user', $user)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return WalletTopupRequest[]
     */
    public function findPending(): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.status = :status')
            ->setParameter('status', WalletTopupRequest::STATUS_PENDING)
            ->orderBy('t.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return WalletTopupRequest[]
     */
    public function findNewRequests(): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.status = :status')
            ->setParameter('status', WalletTopupRequest::STATUS_PENDING)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return WalletTopupRequest[]
     */
    public function findHistoryRequests(?string $searchQuery = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.user', 'u')
            ->addSelect('u')
            ->where('t.status IN (:finalStatuses)')
            ->setParameter('finalStatuses', [
                WalletTopupRequest::STATUS_APPROVED,
                WalletTopupRequest::STATUS_REJECTED,
            ])
            ->orderBy('t.processedAt', 'DESC')
            ->addOrderBy('t.createdAt', 'DESC');

        if ($searchQuery !== null && $searchQuery !== '') {
            $nameQuery = '%' . mb_strtolower($searchQuery) . '%';
            $digitsOnly = preg_replace('/\D+/', '', $searchQuery) ?? '';

            if ($digitsOnly !== '') {
                $phoneCandidates = [$searchQuery, $digitsOnly];

                if (str_starts_with($digitsOnly, '998')) {
                    $phoneCandidates[] = '+' . $digitsOnly;
                }

                $phoneCandidates = array_values(array_unique(array_filter($phoneCandidates)));

                $phoneConditions = [];
                foreach ($phoneCandidates as $idx => $candidate) {
                    $paramName = 'phoneQuery' . $idx;
                    $phoneConditions[] = 'u.phone LIKE :' . $paramName;
                    $qb->setParameter($paramName, '%' . $candidate . '%');
                }

                $qb->andWhere(
                    'LOWER(u.fullName) LIKE :nameQuery OR (' . implode(' OR ', $phoneConditions) . ')'
                )
                ->setParameter('nameQuery', $nameQuery);
            } else {
                $qb->andWhere('LOWER(u.fullName) LIKE :nameQuery')
                    ->setParameter('nameQuery', $nameQuery);
            }
        }

        return $qb->getQuery()->getResult();
    }

    public function countNewRequests(): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.status = :status')
            ->setParameter('status', WalletTopupRequest::STATUS_PENDING)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countHistoryRequests(): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.status IN (:finalStatuses)')
            ->setParameter('finalStatuses', [
                WalletTopupRequest::STATUS_APPROVED,
                WalletTopupRequest::STATUS_REJECTED,
            ])
            ->getQuery()
            ->getSingleScalarResult();
    }
}