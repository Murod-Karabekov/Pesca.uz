<?php

namespace App\Entity;

use App\Repository\BonusWalletRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BonusWalletRepository::class)]
class BonusWallet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'bonusWallet')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $balance = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $totalEarned = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $totalSpent = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $totalWithdrawn = '0.00';

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getBalance(): string
    {
        return $this->balance;
    }

    public function setBalance(string $balance): static
    {
        $this->balance = $balance;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getTotalEarned(): string
    {
        return $this->totalEarned;
    }

    public function setTotalEarned(string $totalEarned): static
    {
        $this->totalEarned = $totalEarned;
        return $this;
    }

    public function getTotalSpent(): string
    {
        return $this->totalSpent;
    }

    public function setTotalSpent(string $totalSpent): static
    {
        $this->totalSpent = $totalSpent;
        return $this;
    }

    public function getTotalWithdrawn(): string
    {
        return $this->totalWithdrawn;
    }

    public function setTotalWithdrawn(string $totalWithdrawn): static
    {
        $this->totalWithdrawn = $totalWithdrawn;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Balansga qo'shish
     */
    public function credit(string $amount): static
    {
        $this->balance = bcadd($this->balance, $amount, 2);
        $this->totalEarned = bcadd($this->totalEarned, $amount, 2);
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * Balansdan yechish (tovar olish)
     */
    public function debit(string $amount): static
    {
        if (bccomp($this->balance, $amount, 2) < 0) {
            throw new \LogicException('Bonus balansda yetarli mablag\' yo\'q');
        }
        $this->balance = bcsub($this->balance, $amount, 2);
        $this->totalSpent = bcadd($this->totalSpent, $amount, 2);
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * Naqd yechish (faqat VIP)
     */
    public function withdraw(string $amount): static
    {
        if (bccomp($this->balance, $amount, 2) < 0) {
            throw new \LogicException('Bonus balansda yetarli mablag\' yo\'q');
        }
        $this->balance = bcsub($this->balance, $amount, 2);
        $this->totalWithdrawn = bcadd($this->totalWithdrawn, $amount, 2);
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }
}
