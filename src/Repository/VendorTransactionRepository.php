<?php

namespace App\Repository;

use App\Entity\Vendor;
use App\Entity\VendorTransaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VendorTransaction>
 */
class VendorTransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VendorTransaction::class);
    }

    /** Har bir vendor uchun jami komissiya summasi (unsettled) */
    public function sumUnsettledCommission(Vendor $vendor): float
    {
        return (float)$this->createQueryBuilder('t')
            ->select('SUM(t.commissionAmount)')
            ->where('t.vendor = :vendor')
            ->andWhere('t.status = :status')
            ->setParameter('vendor', $vendor)
            ->setParameter('status', VendorTransaction::STATUS_CONFIRMED)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
