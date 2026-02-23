<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['phone'], message: 'This phone number is already registered.')]
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

    /** @var Collection<int, Cart> */
    #[ORM\OneToMany(targetEntity: Cart::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $cartItems;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->cartItems = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
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
