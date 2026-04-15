<?php

namespace App\Entity;

use App\Repository\VendorTransactionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Do'konchi "Tayyor" bosganda yoziladi.
 * Admin barcha tranzaksiyalarni kuzatib boradi.
 */
#[ORM\Entity(repositoryClass: VendorTransactionRepository::class)]
#[ORM\Index(columns: ['vendor_id'], name: 'idx_vtx_vendor')]
#[ORM\Index(columns: ['order_id'], name: 'idx_vtx_order')]
class VendorTransaction
{
    public const STATUS_CONFIRMED = 'confirmed'; // do'konchi tasdiqladi
    public const STATUS_SETTLED   = 'settled';   // admin komissiyasini undirdi

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'transactions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Vendor $vendor = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $order = null;

    /** Do'konchi kiritgan savdo summasi */
    #[ORM\Column(type: Types::DECIMAL, precision: 14, scale: 2)]
    private string $saleAmount = '0.00';

    /** Platformaning komissiyasi (saleAmount * commissionRate / 100) */
    #[ORM\Column(type: Types::DECIMAL, precision: 14, scale: 2)]
    private string $commissionAmount = '0.00';

    /** Qaysi komissiya foizi ishlatilgan */
    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private string $commissionRate = '0.00';

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_CONFIRMED;

    /** Admin tomonidan "settled" qilingan vaqt */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $settledAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getVendor(): ?Vendor { return $this->vendor; }
    public function setVendor(?Vendor $vendor): static { $this->vendor = $vendor; return $this; }

    public function getOrder(): ?Order { return $this->order; }
    public function setOrder(?Order $order): static { $this->order = $order; return $this; }

    public function getSaleAmount(): string { return $this->saleAmount; }
    public function setSaleAmount(string $saleAmount): static { $this->saleAmount = $saleAmount; return $this; }

    public function getCommissionAmount(): string { return $this->commissionAmount; }
    public function setCommissionAmount(string $commissionAmount): static { $this->commissionAmount = $commissionAmount; return $this; }

    public function getCommissionRate(): string { return $this->commissionRate; }
    public function setCommissionRate(string $commissionRate): static { $this->commissionRate = $commissionRate; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getSettledAt(): ?\DateTimeImmutable { return $this->settledAt; }
    public function setSettledAt(?\DateTimeImmutable $settledAt): static { $this->settledAt = $settledAt; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }

    /** Do'konchi qo'lida qoladigan sum (komissiyadan keyin) */
    public function getVendorAmount(): string
    {
        return (string)((float)$this->saleAmount - (float)$this->commissionAmount);
    }
}
