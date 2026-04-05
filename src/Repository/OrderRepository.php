<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * @return Order[]
     */
    public function findNewOrders(): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.items', 'i')
            ->addSelect('i')
            ->where('o.paymentStatus = :pending')
            ->setParameter('pending', Order::PAYMENT_STATUS_PENDING)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Order[]
     */
    public function findHistoryOrders(?string $searchQuery = null): array
    {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.items', 'i')
            ->addSelect('i')
            ->where('o.paymentStatus IN (:finalStatuses)')
            ->setParameter('finalStatuses', [
                Order::PAYMENT_STATUS_APPROVED,
                Order::PAYMENT_STATUS_REJECTED,
            ])
            ->orderBy('o.approvedAt', 'DESC')
            ->addOrderBy('o.createdAt', 'DESC');

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
                    $phoneConditions[] = 'o.customerPhone LIKE :' . $paramName;
                    $qb->setParameter($paramName, '%' . $candidate . '%');
                }

                $qb->andWhere(
                    'LOWER(o.customerFullName) LIKE :nameQuery OR (' . implode(' OR ', $phoneConditions) . ')'
                )
                ->setParameter('nameQuery', $nameQuery);
            } else {
                $qb->andWhere('LOWER(o.customerFullName) LIKE :nameQuery')
                    ->setParameter('nameQuery', $nameQuery);
            }
        }

        return $qb->getQuery()->getResult();
    }

    public function countHistoryOrders(): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.paymentStatus IN (:finalStatuses)')
            ->setParameter('finalStatuses', [
                Order::PAYMENT_STATUS_APPROVED,
                Order::PAYMENT_STATUS_REJECTED,
            ])
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countNewOrders(): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.paymentStatus = :pending')
            ->setParameter('pending', Order::PAYMENT_STATUS_PENDING)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
