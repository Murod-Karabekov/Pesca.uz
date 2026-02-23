<?php

namespace App\Repository;

use App\Entity\Cart;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cart>
 */
class CartRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cart::class);
    }

    /**
     * @return Cart[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.product', 'p')
            ->addSelect('p')
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findExistingCartItem(User $user, int $productId, string $size): ?Cart
    {
        return $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->andWhere('c.product = :product')
            ->andWhere('c.size = :size')
            ->setParameter('user', $user)
            ->setParameter('product', $productId)
            ->setParameter('size', $size)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function clearCart(User $user): void
    {
        $this->createQueryBuilder('c')
            ->delete()
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    public function getCartTotal(User $user): float
    {
        $items = $this->findByUser($user);
        $total = 0;
        foreach ($items as $item) {
            $total += $item->getTotal();
        }
        return $total;
    }
}
