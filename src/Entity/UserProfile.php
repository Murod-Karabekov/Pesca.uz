<?php

namespace App\Entity;

use App\Repository\UserProfileRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserProfileRepository::class)]
class UserProfile
{
    public const GENDERS = ['male', 'female'];
    public const SKIN_TONES = ['light', 'warm_medium', 'cool_medium', 'dark'];
    public const FACE_SHAPES = ['oval', 'round', 'square', 'heart', 'oblong', 'diamond'];

    public const OCCASIONS = [
        'office',
        'study',
        'event',
        'casual_street',
        'sport',
        'travel',
        'home',
        'other',
    ];

    public const STYLE_INTENTS = [
        'minimal',
        'classic',
        'street',
        'elegant',
        'trendy',
        'sport_chic',
    ];

    public const SEASONS = ['spring', 'summer', 'autumn', 'winter'];

    public const BODY_TYPES = [
        'hourglass',
        'pear',
        'apple',
        'rectangle',
        'inverted_triangle',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'profile')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $gender = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $skinTone = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $faceShape = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $analyzedAt = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $occasion = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $styleIntent = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $season = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $heightCm = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $shoulderCm = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $chestCm = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $waistCm = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $hipCm = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $bodyType = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getSkinTone(): ?string
    {
        return $this->skinTone;
    }

    public function setSkinTone(?string $skinTone): static
    {
        $this->skinTone = $skinTone;
        return $this;
    }

    public function getFaceShape(): ?string
    {
        return $this->faceShape;
    }

    public function setFaceShape(?string $faceShape): static
    {
        $this->faceShape = $faceShape;
        return $this;
    }

    public function getAnalyzedAt(): ?\DateTimeImmutable
    {
        return $this->analyzedAt;
    }

    public function setAnalyzedAt(?\DateTimeImmutable $analyzedAt): static
    {
        $this->analyzedAt = $analyzedAt;
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

    public function getGenderLabel(): string
    {
        return match ($this->gender) {
            'male' => 'Erkak',
            'female' => 'Ayol',
            default => 'Aniqlanmagan',
        };
    }

    public function isAnalyzed(): bool
    {
        return $this->skinTone !== null && $this->faceShape !== null;
    }

    public function getSkinToneLabel(): string
    {
        return match ($this->skinTone) {
            'light' => 'Light (Oq teri)',
            'warm_medium' => 'Iliq bug\'doy',
            'cool_medium' => 'Sovuq bug\'doy',
            'dark' => 'To\'q teri',
            default => 'Aniqlanmagan',
        };
    }

    public function getFaceShapeLabel(): string
    {
        return match ($this->faceShape) {
            'oval' => 'Oval',
            'round' => 'Dumaloq',
            'square' => 'To\'rtburchak',
            'heart' => 'Yurak',
            'oblong' => 'Cho\'ziq',
            'diamond' => 'Olmos',
            default => 'Aniqlanmagan',
        };
    }

    public function getOccasion(): ?string
    {
        return $this->occasion;
    }

    public function setOccasion(?string $occasion): static
    {
        $this->occasion = $occasion;
        return $this;
    }

    public function getOccasionLabel(): string
    {
        return match ($this->occasion) {
            'office' => 'Ish / Ofis',
            'study' => 'O\'qish',
            'event' => 'Bazm / Tadbir',
            'casual_street' => 'Kundalik / Ko\'cha',
            'sport' => 'Sport / Aktiv',
            'travel' => 'Sayohat',
            'home' => 'Uy / Casual',
            'other' => 'Boshqa',
            default => 'Tanlanmagan',
        };
    }

    public function getStyleIntent(): ?string
    {
        return $this->styleIntent;
    }

    public function setStyleIntent(?string $styleIntent): static
    {
        $this->styleIntent = $styleIntent;
        return $this;
    }

    public function getStyleIntentLabel(): string
    {
        return match ($this->styleIntent) {
            'minimal' => 'Minimal',
            'classic' => 'Klassik',
            'street' => 'Street',
            'elegant' => 'Elegant',
            'trendy' => 'Trendy',
            'sport_chic' => 'Sport-chic',
            default => 'Tanlanmagan',
        };
    }

    public function getSeason(): ?string
    {
        return $this->season;
    }

    public function setSeason(?string $season): static
    {
        $this->season = $season;
        return $this;
    }

    public function getSeasonLabel(): string
    {
        return match ($this->season) {
            'spring' => 'Bahor',
            'summer' => 'Yoz',
            'autumn' => 'Kuz',
            'winter' => 'Qish',
            default => 'Tanlanmagan',
        };
    }

    public function getHeightCm(): ?int
    {
        return $this->heightCm;
    }

    public function setHeightCm(?int $heightCm): static
    {
        $this->heightCm = $heightCm;
        return $this;
    }

    public function getShoulderCm(): ?int
    {
        return $this->shoulderCm;
    }

    public function setShoulderCm(?int $shoulderCm): static
    {
        $this->shoulderCm = $shoulderCm;
        return $this;
    }

    public function getChestCm(): ?int
    {
        return $this->chestCm;
    }

    public function setChestCm(?int $chestCm): static
    {
        $this->chestCm = $chestCm;
        return $this;
    }

    public function getWaistCm(): ?int
    {
        return $this->waistCm;
    }

    public function setWaistCm(?int $waistCm): static
    {
        $this->waistCm = $waistCm;
        return $this;
    }

    public function getHipCm(): ?int
    {
        return $this->hipCm;
    }

    public function setHipCm(?int $hipCm): static
    {
        $this->hipCm = $hipCm;
        return $this;
    }

    public function getBodyType(): ?string
    {
        return $this->bodyType;
    }

    public function setBodyType(?string $bodyType): static
    {
        $this->bodyType = $bodyType;
        return $this;
    }

    /**
     * Mannequin PNG yo'lini qaytaradi (gender + canonical body type bo'yicha)
     */
    public function getMannequinImage(): ?string
    {
        if (!$this->bodyType || !$this->gender) {
            return null;
        }

        if ($this->gender === 'male') {
            $file = match ($this->bodyType) {
                'hourglass'         => 'trapezoid',
                'pear'              => 'triangle',
                'apple'             => 'oval',
                'rectangle'         => 'rectangle',
                'inverted_triangle' => 'inverted_triangle',
                default             => null,
            };
            return $file ? '/images/mannequins/man/' . $file . '.png' : null;
        }

        return '/images/mannequins/woman/' . $this->bodyType . '.png';
    }

    public function getBodyTypeLabel(): string
    {
        if ($this->gender === 'male') {
            return match ($this->bodyType) {
                'hourglass'         => 'Trapeziya (Trapezoid)',
                'pear'              => 'Uchburchak (Triangle)',
                'apple'             => 'Oval',
                'rectangle'         => 'To\'rtburchak (Rectangle)',
                'inverted_triangle' => 'Teskari uchburchak',
                default             => 'Aniqlanmagan',
            };
        }

        return match ($this->bodyType) {
            'hourglass'         => 'Soat qum (Hourglass)',
            'pear'              => 'Nok (Pear)',
            'apple'             => 'Olma (Apple)',
            'rectangle'         => 'To\'rtburchak (Rectangle)',
            'inverted_triangle' => 'Teskari uchburchak',
            default             => 'Aniqlanmagan',
        };
    }

    /**
     * Profilni tozalash
     */
    public function clear(): static
    {
        $this->gender = null;
        $this->skinTone = null;
        $this->faceShape = null;
        $this->analyzedAt = null;
        $this->occasion = null;
        $this->styleIntent = null;
        $this->season = null;
        $this->heightCm = null;
        $this->shoulderCm = null;
        $this->chestCm = null;
        $this->waistCm = null;
        $this->hipCm = null;
        $this->bodyType = null;
        return $this;
    }
}
