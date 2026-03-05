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
        return $this->gender !== null && $this->skinTone !== null && $this->faceShape !== null;
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

    /**
     * Profilni tozalash
     */
    public function clear(): static
    {
        $this->gender = null;
        $this->skinTone = null;
        $this->faceShape = null;
        $this->analyzedAt = null;
        return $this;
    }
}
