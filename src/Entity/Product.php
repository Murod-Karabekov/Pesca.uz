<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    public const SIZES = ['XS', 'S', 'M', 'L', 'XL'];

    public const GENDERS = [
        'male' => 'Erkak',
        'female' => 'Ayol',
        'unisex' => 'Unisex',
    ];

    public const SKIN_TONES = [
        'light' => 'Light (Oq teri)',
        'warm_medium' => 'Warm Medium (Iliq bug\'doy)',
        'cool_medium' => 'Cool Medium (Sovuq bug\'doy)',
        'dark' => 'Dark (To\'q teri)',
    ];

    public const FACE_SHAPES = [
        'oval' => 'Oval',
        'round' => 'Round (Dumaloq)',
        'square' => 'Square (To\'rtburchak)',
        'heart' => 'Heart (Yurak)',
        'oblong' => 'Oblong (Cho\'ziq)',
        'diamond' => 'Diamond (Olmos)',
    ];

    public const OCCASIONS = [
        'office'        => 'Ish / Ofis',
        'study'         => 'O\'qish',
        'event'         => 'Bazm / Tadbir',
        'casual_street' => 'Kundalik / Ko\'cha',
        'sport'         => 'Sport / Aktiv',
        'travel'        => 'Sayohat',
        'home'          => 'Uy / Casual',
        'other'         => 'Boshqa',
    ];

    public const STYLE_INTENTS = [
        'minimal'    => 'Minimal',
        'classic'    => 'Klassik',
        'street'     => 'Street',
        'elegant'    => 'Elegant',
        'trendy'     => 'Trendy',
        'sport_chic' => 'Sport-chic',
    ];

    public const SEASONS = [
        'spring' => 'Bahor',
        'summer' => 'Yoz',
        'autumn' => 'Kuz',
        'winter' => 'Qish',
    ];

    public const BODY_TYPES = [
        'hourglass'         => 'Soat qum (Hourglass)',
        'pear'              => 'Nok (Pear)',
        'apple'             => 'Olma (Apple)',
        'rectangle'         => 'To\'rtburchak (Rectangle)',
        'inverted_triangle' => 'Teskari uchburchak',
    ];


    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Product name is required.')]
    #[Assert\Length(min: 2, max: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Price is required.')]
    #[Assert\Positive(message: 'Price must be a positive number.')]
    private ?string $price = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::JSON)]
    private array $size = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column]
    private bool $status = true;

    #[ORM\ManyToOne(inversedBy: 'products')]
    private ?Category $category = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $gender = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $skinTones = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $faceShapes = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $occasions = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $styleIntents = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $seasons = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $bodyTypes = null;


    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /** @var Collection<int, Cart> */
    #[ORM\OneToMany(targetEntity: Cart::class, mappedBy: 'product', orphanRemoval: true)]
    private Collection $cartItems;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->cartItems = new ArrayCollection();
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

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;
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

    public function getSize(): array
    {
        return $this->size;
    }

    public function setSize(array $size): static
    {
        $this->size = $size;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function isExternalImage(): bool
    {
        return $this->image !== null && str_starts_with($this->image, 'http');
    }

    public function getImageSrc(): ?string
    {
        if ($this->image === null) {
            return null;
        }

        return $this->isExternalImage()
            ? $this->image
            : '/uploads/products/' . $this->image;
    }

    public function isStatus(): bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): static
    {
        $this->status = $status;
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

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): static
    {
        $this->gender = $gender;
        return $this;
    }

    public function getSkinTones(): ?array
    {
        return $this->skinTones;
    }

    public function setSkinTones(?array $skinTones): static
    {
        $this->skinTones = $skinTones;
        return $this;
    }

    public function getFaceShapes(): ?array
    {
        return $this->faceShapes;
    }

    public function setFaceShapes(?array $faceShapes): static
    {
        $this->faceShapes = $faceShapes;
        return $this;
    }

    public function getOccasions(): ?array
    {
        return $this->occasions;
    }

    public function setOccasions(?array $occasions): static
    {
        $this->occasions = $occasions;
        return $this;
    }

    public function getStyleIntents(): ?array
    {
        return $this->styleIntents;
    }

    public function setStyleIntents(?array $styleIntents): static
    {
        $this->styleIntents = $styleIntents;
        return $this;
    }

    public function getSeasons(): ?array
    {
        return $this->seasons;
    }

    public function setSeasons(?array $seasons): static
    {
        $this->seasons = $seasons;
        return $this;
    }

    public function getBodyTypes(): ?array
    {
        return $this->bodyTypes;
    }

    public function setBodyTypes(?array $bodyTypes): static
    {
        $this->bodyTypes = $bodyTypes;
        return $this;
    }

    /**
     * SmartStyle match score hisoblash (max 100).
     *
     * - skinTone:  40 pts
     * - faceShape: 30 pts
     * - occasion:  +15 bonus (faqat ikkala tomon teglanganida)
     * - bodyType:  +10 bonus
     * - season:    +5  bonus
     */
    public function getMatchScore(
        string  $skinTone,
        string  $faceShape,
        ?string $occasion  = null,
        ?string $bodyType  = null,
        ?string $season    = null,
    ): int {
        $score = 0;

        if ($this->skinTones && in_array($skinTone, $this->skinTones, true)) {
            $score += 40;
        }
        if ($this->faceShapes && in_array($faceShape, $this->faceShapes, true)) {
            $score += 30;
        }
        if ($occasion && $this->occasions && in_array($occasion, $this->occasions, true)) {
            $score += 15;
        }
        if ($bodyType && $this->bodyTypes && in_array($bodyType, $this->bodyTypes, true)) {
            $score += 10;
        }
        if ($season && $this->seasons && in_array($season, $this->seasons, true)) {
            $score += 5;
        }

        return $score;
    }

    /** @return Collection<int, Cart> */
    public function getCartItems(): Collection
    {
        return $this->cartItems;
    }
}
