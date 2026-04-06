<?php

namespace App\Repository;

use App\Entity\Announcement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Announcement>
 */
class AnnouncementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Announcement::class);
    }

    /**
     * @return Announcement[]
     */
    public function findActiveOrdered(): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.isActive = :isActive')
            ->setParameter('isActive', true)
            ->orderBy('a.sortOrder', 'ASC')
            ->addOrderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Announcement[]
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.sortOrder', 'ASC')
            ->addOrderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Announcement[]
     */
    public function findActiveBannersOrdered(): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.isActive = :isActive')
            ->andWhere('a.isBanner = :isBanner')
            ->setParameter('isActive', true)
            ->setParameter('isBanner', true)
            ->orderBy('a.sortOrder', 'ASC')
            ->addOrderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
