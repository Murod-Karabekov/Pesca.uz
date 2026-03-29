<?php

namespace App\Entity;

use App\Repository\MembershipPlanRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MembershipPlanRepository::class)]
#[UniqueEntity(fields: ['slug'], message: 'Bu slug allaqachon mavjud.')]
class MembershipPlan
{
    public const SLUG_FREE = 'free';
    public const SLUG_START = 'start';
    public const SLUG_PREMIUM = 'premium';
    public const SLUG_VIP = 'vip';

    public const INTERFACE_BASIC = 'basic';
    public const INTERFACE_START = 'start';
    public const INTERFACE_PREMIUM = 'premium';
    public const INTERFACE_PREMIUM_VIP = 'premium_vip';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank]
    private ?string $slug = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    #[Assert\PositiveOrZero]
    private ?string $price = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $productReferralPercent = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $planReferralPercent = null;

    #[ORM\Column]
    private bool $canWithdraw = false;

    #[ORM\Column]
    private bool $hasFinanceSection = false;

    #[ORM\Column(length: 30)]
    private string $interfaceType = self::INTERFACE_BASIC;

    #[ORM\Column]
    private int $clothingCount = 0;

    #[ORM\Column]
    private bool $hasDesign = false;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private int $sortOrder = 0;

    /** @var Collection<int, UserMembership> */
    #[ORM\OneToMany(targetEntity: UserMembership::class, mappedBy: 'plan')]
    private Collection $memberships;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->memberships = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getProductReferralPercent(): ?string
    {
        return $this->productReferralPercent;
    }

    public function setProductReferralPercent(string $productReferralPercent): static
    {
        $this->productReferralPercent = $productReferralPercent;
        return $this;
    }

    public function getPlanReferralPercent(): ?string
    {
        return $this->planReferralPercent;
    }

    public function setPlanReferralPercent(string $planReferralPercent): static
    {
        $this->planReferralPercent = $planReferralPercent;
        return $this;
    }

    public function isCanWithdraw(): bool
    {
        return $this->canWithdraw;
    }

    public function setCanWithdraw(bool $canWithdraw): static
    {
        $this->canWithdraw = $canWithdraw;
        return $this;
    }

    public function isHasFinanceSection(): bool
    {
        return $this->hasFinanceSection;
    }

    public function setHasFinanceSection(bool $hasFinanceSection): static
    {
        $this->hasFinanceSection = $hasFinanceSection;
        return $this;
    }

    public function getInterfaceType(): string
    {
        return $this->interfaceType;
    }

    public function setInterfaceType(string $interfaceType): static
    {
        $this->interfaceType = $interfaceType;
        return $this;
    }

    public function getClothingCount(): int
    {
        return $this->clothingCount;
    }

    public function setClothingCount(int $clothingCount): static
    {
        $this->clothingCount = $clothingCount;
        return $this;
    }

    public function isHasDesign(): bool
    {
        return $this->hasDesign;
    }

    public function setHasDesign(bool $hasDesign): static
    {
        $this->hasDesign = $hasDesign;
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

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    /** @return Collection<int, UserMembership> */
    public function getMemberships(): Collection
    {
        return $this->memberships;
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

    public function isFree(): bool
    {
        return $this->slug === self::SLUG_FREE;
    }

    public function isPaid(): bool
    {
        return !$this->isFree();
    }

    /**
     * Ushbu tarif berilgan darajadagi mahsulotni olish huquqiga egami?
     */
    public function canAccessProductLevel(string $requiredLevel): bool
    {
        $hierarchy = [
            self::SLUG_FREE => 0,
            self::SLUG_START => 1,
            self::SLUG_PREMIUM => 2,
            self::SLUG_VIP => 3,
        ];

        $userLevel = $hierarchy[$this->slug] ?? 0;
        $requiredLevelNum = $hierarchy[$requiredLevel] ?? 0;

        return $userLevel >= $requiredLevelNum;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
