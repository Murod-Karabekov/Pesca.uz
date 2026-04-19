<?php

namespace App\Repository;

use App\Entity\CorporatePartnershipRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CorporatePartnershipRequest>
 */
class CorporatePartnershipRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CorporatePartnershipRequest::class);
    }

    /**
     * @return CorporatePartnershipRequest[]
     */
    public function findAllRecent(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
