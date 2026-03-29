<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['phone'], message: 'This phone number is already registered.')]
#[UniqueEntity(fields: ['referralCode'], message: 'Bu referral kod allaqachon mavjud.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please enter your full name.')]
    #[Assert\Length(min: 2, max: 255)]
    private ?string $fullName = null;

    #[ORM\Column(length: 20, unique: true)]
    #[Assert\NotBlank(message: 'Please enter your phone number.')]
    #[Assert\Regex(pattern: '/^\+?[0-9]{9,15}$/', message: 'Please enter a valid phone number.')]
    private ?string $phone = null;

    #[ORM\Column]
    private ?string $password = null;

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $roles = [];

    /** Unique referral kodi (har bir foydalanuvchiga) */
    #[ORM\Column(length: 20, unique: true, nullable: true)]
    private ?string $referralCode = null;

    /** Kim olib keldi (referrer) */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $referredBy = null;

    /** Joriy plan slug'i: free, start, premium, vip */
    #[ORM\Column(length: 20)]
    private string $currentPlan = 'free';

    /** @var Collection<int, Cart> */
    #[ORM\OneToMany(targetEntity: Cart::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $cartItems;

    /** @var Collection<int, UserMembership> */
    #[ORM\OneToMany(targetEntity: UserMembership::class, mappedBy: 'user')]
    private Collection $memberships;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?UserProfile $profile = null;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?BonusWallet $bonusWallet = null;

    /** Umumiy balans (admin tomonidan boshqariladi, tarif uchun yechiladi) */
    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $generalBalance = '0.00';

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->cartItems = new ArrayCollection();
        $this->memberships = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->referralCode = $this->generateReferralCode();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): static
    {
        $this->fullName = $fullName;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    /** @param list<string> $roles */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->phone;
    }

    public function eraseCredentials(): void
    {
        // Clear temporary sensitive data
    }

    /** @return Collection<int, Cart> */
    public function getCartItems(): Collection
    {
        return $this->cartItems;
    }

    public function addCartItem(Cart $cartItem): static
    {
        if (!$this->cartItems->contains($cartItem)) {
            $this->cartItems->add($cartItem);
            $cartItem->setUser($this);
        }
        return $this;
    }

    public function removeCartItem(Cart $cartItem): static
    {
        if ($this->cartItems->removeElement($cartItem)) {
            if ($cartItem->getUser() === $this) {
                $cartItem->setUser(null);
            }
        }
        return $this;
    }

    public function getProfile(): ?UserProfile
    {
        return $this->profile;
    }

    public function setProfile(?UserProfile $profile): static
    {
        // unset the owning side of the relation if necessary
        if ($profile === null && $this->profile !== null) {
            $this->profile->setUser(null);
        }

        // set the owning side of the relation if necessary
        if ($profile !== null && $profile->getUser() !== $this) {
            $profile->setUser($this);
        }

        $this->profile = $profile;
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

    // ─── Referral & Membership ─────────────────────────────

    public function getGeneralBalance(): string
    {
        return $this->generalBalance;
    }

    public function setGeneralBalance(string $generalBalance): static
    {
        $this->generalBalance = $generalBalance;
        return $this;
    }

    public function addGeneralBalance(string $amount): static
    {
        $this->generalBalance = bcadd($this->generalBalance, $amount, 2);
        return $this;
    }

    public function deductGeneralBalance(string $amount): static
    {
        if (bccomp($this->generalBalance, $amount, 2) < 0) {
            throw new \LogicException('Umumiy balansda yetarli mablag\' yo\'q');
        }
        $this->generalBalance = bcsub($this->generalBalance, $amount, 2);
        return $this;
    }

    public function getReferralCode(): ?string
    {
        return $this->referralCode;
    }

    public function setReferralCode(?string $referralCode): static
    {
        $this->referralCode = $referralCode;
        return $this;
    }

    public function getReferredBy(): ?User
    {
        return $this->referredBy;
    }

    public function setReferredBy(?User $referredBy): static
    {
        $this->referredBy = $referredBy;
        return $this;
    }

    public function getCurrentPlan(): string
    {
        return $this->currentPlan;
    }

    public function setCurrentPlan(string $currentPlan): static
    {
        $this->currentPlan = $currentPlan;
        return $this;
    }

    /** @return Collection<int, UserMembership> */
    public function getMemberships(): Collection
    {
        return $this->memberships;
    }

    public function getBonusWallet(): ?BonusWallet
    {
        return $this->bonusWallet;
    }

    public function setBonusWallet(?BonusWallet $bonusWallet): static
    {
        $this->bonusWallet = $bonusWallet;
        return $this;
    }

    /**
     * Foydalanuvchi pullik tarifga a'zomi?
     */
    public function hasMembership(): bool
    {
        return $this->currentPlan !== 'free';
    }

    /**
     * VIP a'zomi?
     */
    public function isVip(): bool
    {
        return $this->currentPlan === 'vip';
    }

    /**
     * Berilgan darajadagi mahsulotni olish huquqi bormi?
     */
    public function canAccessProductLevel(string $requiredLevel): bool
    {
        if ($requiredLevel === 'free') {
            return true;
        }

        // A'zolar uchun mahsulot: start/premium a'zolari olishi mumkin
        if (in_array($requiredLevel, ['start', 'premium'])) {
            return $this->hasMembership();
        }

        // VIP mahsulot: faqat VIP olishi mumkin
        if ($requiredLevel === 'vip') {
            return $this->isVip();
        }

        return false;
    }

    public function getCurrentPlanLabel(): string
    {
        return match ($this->currentPlan) {
            'start' => 'START',
            'premium' => 'PREMIUM',
            'vip' => 'VIP',
            default => 'Oddiy',
        };
    }

    public function getCurrentPlanColor(): string
    {
        return match ($this->currentPlan) {
            'start' => 'yellow',
            'premium' => 'blue',
            'vip' => 'purple',
            default => 'gray',
        };
    }

    private function generateReferralCode(): string
    {
        return strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 8));
    }
}
