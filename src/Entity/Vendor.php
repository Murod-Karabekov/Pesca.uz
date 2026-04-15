<?php

namespace App\Entity;

use App\Repository\VendorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: VendorRepository::class)]
class Vendor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $address = null;

    /** Komisyon foizi (masalan: 10 = 10%) */
    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private string $commissionRate = '10.00';

    /** Jami tasdiqlangan savdo summasi (do'konchi virtual kassasi) */
    #[ORM\Column(type: Types::DECIMAL, precision: 14, scale: 2)]
    private string $totalEarnings = '0.00';

    /** Do'kondagi foydalanuvchi (ROLE_VENDOR) */
    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $owner = null;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /** @var Collection<int, Product> */
    #[ORM\OneToMany(targetEntity: Product::class, mappedBy: 'vendor')]
    private Collection $products;

    /** @var Collection<int, VendorTransaction> */
    #[ORM\OneToMany(targetEntity: VendorTransaction::class, mappedBy: 'vendor')]
    private Collection $transactions;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->products = new ArrayCollection();
        $this->transactions = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(?string $phone): static { $this->phone = $phone; return $this; }

    public function getAddress(): ?string { return $this->address; }
    public function setAddress(?string $address): static { $this->address = $address; return $this; }

    public function getCommissionRate(): string { return $this->commissionRate; }
    public function setCommissionRate(string $commissionRate): static { $this->commissionRate = $commissionRate; return $this; }

    public function getTotalEarnings(): string { return $this->totalEarnings; }
    public function setTotalEarnings(string $totalEarnings): static { $this->totalEarnings = $totalEarnings; return $this; }

    public function addToEarnings(string $amount): static
    {
        $this->totalEarnings = (string)((float)$this->totalEarnings + (float)$amount);
        return $this;
    }

    public function getOwner(): ?User { return $this->owner; }
    public function setOwner(?User $owner): static { $this->owner = $owner; return $this; }

    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): static { $this->isActive = $isActive; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }

    /** @return Collection<int, Product> */
    public function getProducts(): Collection { return $this->products; }

    /** @return Collection<int, VendorTransaction> */
    public function getTransactions(): Collection { return $this->transactions; }
}
