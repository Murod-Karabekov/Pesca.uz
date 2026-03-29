<?php

namespace App\Entity;

use App\Repository\BonusTransactionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BonusTransactionRepository::class)]
class BonusTransaction
{
    public const TYPE_PRODUCT_REFERRAL = 'product_referral';
    public const TYPE_PLAN_REFERRAL = 'plan_referral';
    public const TYPE_SPENT = 'spent';
    public const TYPE_WITHDRAWN = 'withdrawn';
    public const TYPE_WITHDRAWAL_PENDING = 'withdrawal_pending';
    public const TYPE_WITHDRAWAL_REFUNDED = 'withdrawal_refunded';
    public const TYPE_ADMIN_TOPUP = 'admin_topup';
    public const TYPE_ADMIN_DEDUCT = 'admin_deduct';
    public const TYPE_PLAN_PAYMENT = 'plan_payment';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 30)]
    private ?string $type = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private ?string $amount = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $description = null;

    /** Kim orqali keldi (referral uchun) */
    #[ORM\ManyToOne]
    private ?User $sourceUser = null;

    /** Qaysi buyurtmadan (mahsulot referral uchun) */
    #[ORM\Column(nullable: true)]
    private ?int $sourceOrderId = null;

    /** Qaysi tarif sotuvdan (plan referral uchun) */
    #[ORM\Column(nullable: true)]
    private ?int $sourceMembershipId = null;

    /** Qancha % qo'llanildi */
    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $appliedPercent = null;

    /** Referrer o'sha paytdagi plan slug'i (freeze uchun) */
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $referrerPlanAtTime = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getSourceUser(): ?User
    {
        return $this->sourceUser;
    }

    public function setSourceUser(?User $sourceUser): static
    {
        $this->sourceUser = $sourceUser;
        return $this;
    }

    public function getSourceOrderId(): ?int
    {
        return $this->sourceOrderId;
    }

    public function setSourceOrderId(?int $sourceOrderId): static
    {
        $this->sourceOrderId = $sourceOrderId;
        return $this;
    }

    public function getSourceMembershipId(): ?int
    {
        return $this->sourceMembershipId;
    }

    public function setSourceMembershipId(?int $sourceMembershipId): static
    {
        $this->sourceMembershipId = $sourceMembershipId;
        return $this;
    }

    public function getAppliedPercent(): ?string
    {
        return $this->appliedPercent;
    }

    public function setAppliedPercent(?string $appliedPercent): static
    {
        $this->appliedPercent = $appliedPercent;
        return $this;
    }

    public function getReferrerPlanAtTime(): ?string
    {
        return $this->referrerPlanAtTime;
    }

    public function setReferrerPlanAtTime(?string $referrerPlanAtTime): static
    {
        $this->referrerPlanAtTime = $referrerPlanAtTime;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_PRODUCT_REFERRAL => 'Mahsulot sotuvdan',
            self::TYPE_PLAN_REFERRAL => 'Tarif sotuvdan',
            self::TYPE_SPENT => 'Tovar olishda ishlatildi',
            self::TYPE_WITHDRAWN => 'Naqd yechildi',
            self::TYPE_WITHDRAWAL_PENDING => 'Naqd yechish so\'rovi',
            self::TYPE_WITHDRAWAL_REFUNDED => 'Yechish rad etildi (qaytarildi)',
            self::TYPE_ADMIN_TOPUP => 'Admin tomonidan to\'ldirildi',
            self::TYPE_ADMIN_DEDUCT => 'Admin tomonidan yechildi',
            self::TYPE_PLAN_PAYMENT => 'Tarif uchun to\'lov',
            default => $this->type ?? '',
        };
    }
}
