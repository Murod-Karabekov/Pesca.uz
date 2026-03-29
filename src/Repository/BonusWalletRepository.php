<?php

namespace App\Repository;

use App\Entity\BonusWallet;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BonusWallet>
 */
class BonusWalletRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BonusWallet::class);
    }

    public function findByUser(User $user): ?BonusWallet
    {
        return $this->findOneBy(['user' => $user]);
    }

    /**
     * Foydalanuvchi uchun bonus wallet yo'q bo'lsa yaratadi
     */
    public function findOrCreateByUser(User $user): BonusWallet
    {
        $wallet = $this->findByUser($user);

        if ($wallet === null) {
            $wallet = new BonusWallet();
            $wallet->setUser($user);
            $this->getEntityManager()->persist($wallet);
            $this->getEntityManager()->flush();
        }

        return $wallet;
    }
}
