<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: 'customer_order')]
#[ORM\Index(columns: ['order_status'], name: 'idx_order_status')]
#[ORM\Index(columns: ['payment_status'], name: 'idx_payment_status')]
#[ORM\Index(columns: ['location_code'], name: 'idx_location_code')]
#[ORM\Index(columns: ['created_at'], name: 'idx_order_created_at')]
class Order
{
    public const LOCATION_JIZZAKH_CITY = 'jizzakh_city';
    public const LOCATION_DUSTLIK_DISTRICT = 'dustlik_district';

    public const ORDER_STATUS_NEW = 'new';
    public const ORDER_STATUS_PAYMENT_PENDING = 'payment_pending';
    public const ORDER_STATUS_PAID = 'paid';
    public const ORDER_STATUS_IN_PRODUCTION = 'in_production';
    public const ORDER_STATUS_READY = 'ready';
    public const ORDER_STATUS_COMPLETED = 'completed';
    public const ORDER_STATUS_CANCELED = 'canceled';

    public const PAYMENT_STATUS_PENDING = 'pending';
    public const PAYMENT_STATUS_APPROVED = 'approved';
    public const PAYMENT_STATUS_REJECTED = 'rejected';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $customerFullName = null;

    #[ORM\Column(length: 20)]
    private ?string $customerPhone = null;

    #[ORM\Column(length: 50)]
    private ?string $locationCode = null;

    #[ORM\Column(length: 100)]
    private ?string $locationLabel = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $subtotalAmount = '0.00';

    #[ORM\Column(length: 3)]
    private string $currency = 'UZS';

    #[ORM\Column(length: 30)]
    private string $orderStatus = self::ORDER_STATUS_PAYMENT_PENDING;

    #[ORM\Column(length: 30)]
    private string $paymentStatus = self::PAYMENT_STATUS_PENDING;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $paymentMethod = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $paymentReference = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adminNote = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $approvedByAdmin = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $approvedAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    /** @var Collection<int, OrderItem> */
    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'order', orphanRemoval: true, cascade: ['persist'])]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public static function getAllowedLocations(): array
    {
        return [
            self::LOCATION_JIZZAKH_CITY => 'Jizzax shahar',
            self::LOCATION_DUSTLIK_DISTRICT => 'Do\'stlik tumani',
        ];
    }

    public static function getLocationLabelByCode(string $code): ?string
    {
        return self::getAllowedLocations()[$code] ?? null;
    }

    public function touch(): void
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

    public function getCustomerFullName(): ?string
    {
        return $this->customerFullName;
    }

    public function setCustomerFullName(string $customerFullName): static
    {
        $this->customerFullName = $customerFullName;
        return $this;
    }

    public function getCustomerPhone(): ?string
    {
        return $this->customerPhone;
    }

    public function setCustomerPhone(string $customerPhone): static
    {
        $this->customerPhone = $customerPhone;
        return $this;
    }

    public function getLocationCode(): ?string
    {
        return $this->locationCode;
    }

    public function setLocationCode(string $locationCode): static
    {
        $this->locationCode = $locationCode;
        return $this;
    }

    public function getLocationLabel(): ?string
    {
        return $this->locationLabel;
    }

    public function setLocationLabel(string $locationLabel): static
    {
        $this->locationLabel = $locationLabel;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    public function getSubtotalAmount(): string
    {
        return $this->subtotalAmount;
    }

    public function setSubtotalAmount(string $subtotalAmount): static
    {
        $this->subtotalAmount = $subtotalAmount;
        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;
        return $this;
    }

    public function getOrderStatus(): string
    {
        return $this->orderStatus;
    }

    public function setOrderStatus(string $orderStatus): static
    {
        $this->orderStatus = $orderStatus;
        $this->touch();
        return $this;
    }

    public function getPaymentStatus(): string
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(string $paymentStatus): static
    {
        $this->paymentStatus = $paymentStatus;
        $this->touch();
        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?string $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    public function getPaymentReference(): ?string
    {
        return $this->paymentReference;
    }

    public function setPaymentReference(?string $paymentReference): static
    {
        $this->paymentReference = $paymentReference;
        return $this;
    }

    public function getAdminNote(): ?string
    {
        return $this->adminNote;
    }

    public function setAdminNote(?string $adminNote): static
    {
        $this->adminNote = $adminNote;
        return $this;
    }

    public function getApprovedByAdmin(): ?User
    {
        return $this->approvedByAdmin;
    }

    public function setApprovedByAdmin(?User $approvedByAdmin): static
    {
        $this->approvedByAdmin = $approvedByAdmin;
        return $this;
    }

    public function getApprovedAt(): ?\DateTimeImmutable
    {
        return $this->approvedAt;
    }

    public function setApprovedAt(?\DateTimeImmutable $approvedAt): static
    {
        $this->approvedAt = $approvedAt;
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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /** @return Collection<int, OrderItem> */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(OrderItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setOrder($this);
        }

        return $this;
    }

    public function removeItem(OrderItem $item): static
    {
        if ($this->items->removeElement($item)) {
            if ($item->getOrder() === $this) {
                $item->setOrder(null);
            }
        }

        return $this;
    }

    public function canApprovePayment(): bool
    {
        return $this->paymentStatus === self::PAYMENT_STATUS_PENDING;
    }

    /**
     * Returns allowed next order statuses from the current status.
     * @return string[]
     */
    public static function getAllowedTransitions(): array
    {
        return [
            self::ORDER_STATUS_PAYMENT_PENDING => [self::ORDER_STATUS_CANCELED],
            self::ORDER_STATUS_PAID            => [self::ORDER_STATUS_IN_PRODUCTION, self::ORDER_STATUS_CANCELED],
            self::ORDER_STATUS_IN_PRODUCTION   => [self::ORDER_STATUS_READY, self::ORDER_STATUS_CANCELED],
            self::ORDER_STATUS_READY           => [self::ORDER_STATUS_COMPLETED],
            self::ORDER_STATUS_COMPLETED       => [],
            self::ORDER_STATUS_CANCELED        => [],
            self::ORDER_STATUS_NEW             => [self::ORDER_STATUS_PAYMENT_PENDING, self::ORDER_STATUS_CANCELED],
        ];
    }

    public function canTransitionTo(string $newStatus): bool
    {
        $allowed = self::getAllowedTransitions()[$this->orderStatus] ?? [];
        return in_array($newStatus, $allowed, true);
    }

    public function getAvailableNextStatuses(): array
    {
        return self::getAllowedTransitions()[$this->orderStatus] ?? [];
    }

    public function getOrderStatusLabel(): string
    {
        return match ($this->orderStatus) {
            self::ORDER_STATUS_NEW => 'Yangi',
            self::ORDER_STATUS_PAYMENT_PENDING => 'To\'lov kutilmoqda',
            self::ORDER_STATUS_PAID => 'To\'langan',
            self::ORDER_STATUS_IN_PRODUCTION => 'Tayyorlanmoqda',
            self::ORDER_STATUS_READY => 'Tayyor',
            self::ORDER_STATUS_COMPLETED => 'Yakunlangan',
            self::ORDER_STATUS_CANCELED => 'Bekor qilingan',
            default => $this->orderStatus,
        };
    }

    public function getPaymentStatusLabel(): string
    {
        return match ($this->paymentStatus) {
            self::PAYMENT_STATUS_PENDING => 'Kutilmoqda',
            self::PAYMENT_STATUS_APPROVED => 'Tasdiqlangan',
            self::PAYMENT_STATUS_REJECTED => 'Rad etilgan',
            default => $this->paymentStatus,
        };
    }
}
