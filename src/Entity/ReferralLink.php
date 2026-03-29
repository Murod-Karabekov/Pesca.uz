<?php

namespace App\Entity;

use App\Repository\ReferralLinkRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Referral bog'lanishlar — ❄️ FREEZE sistema
 * 
 * Har bir referral uchun referrer'ning O'SHA PAYTdagi foizlari muzlatiladi.
 * Referrer keyinchalik tarifini o'zgartirsa ham, eski referallar eski foizda qoladi.
 */
#[ORM\Entity(repositoryClass: ReferralLinkRepository::class)]
#[ORM\UniqueConstraint(name: 'referrer_referred_unique', columns: ['referrer_id', 'referred_id'])]
class ReferralLink
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Kim taklif qildi */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $referrer = null;

    /** Kim keldi */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $referred = null;

    /** ❄️ Referrer o'sha paytdagi plan slug'i */
    #[ORM\Column(length: 20)]
    private ?string $referrerPlanAtTime = null;

    /** ❄️ Muzlatilgan mahsulot referral % */
    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $productPercent = null;

    /** ❄️ Muzlatilgan tarif referral % */
    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $planPercent = null;

    #[ORM\Column]
    private bool $isActive = true;

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

    public function getReferrer(): ?User
    {
        return $this->referrer;
    }

    public function setReferrer(?User $referrer): static
    {
        $this->referrer = $referrer;
        return $this;
    }

    public function getReferred(): ?User
    {
        return $this->referred;
    }

    public function setReferred(?User $referred): static
    {
        $this->referred = $referred;
        return $this;
    }

    public function getReferrerPlanAtTime(): ?string
    {
        return $this->referrerPlanAtTime;
    }

    public function setReferrerPlanAtTime(string $referrerPlanAtTime): static
    {
        $this->referrerPlanAtTime = $referrerPlanAtTime;
        return $this;
    }

    public function getProductPercent(): ?string
    {
        return $this->productPercent;
    }

    public function setProductPercent(string $productPercent): static
    {
        $this->productPercent = $productPercent;
        return $this;
    }

    public function getPlanPercent(): ?string
    {
        return $this->planPercent;
    }

    public function setPlanPercent(string $planPercent): static
    {
        $this->planPercent = $planPercent;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
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
}
