<?php

namespace App\Entity;

use App\Repository\SmartStyleScanHistoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SmartStyleScanHistoryRepository::class)]
#[ORM\Table(name: 'smart_style_scan_history')]
#[ORM\Index(name: 'idx_smartstyle_hist_user_created', columns: ['user_id', 'created_at'])]
class SmartStyleScanHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON)]
    private array $profileSnapshot = [];

    /** @var list<array<string, mixed>> */
    #[ORM\Column(type: Types::JSON)]
    private array $recommendationsSnapshot = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photoFilename = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /** @return array<string, mixed> */
    public function getProfileSnapshot(): array
    {
        return $this->profileSnapshot;
    }

    /** @param array<string, mixed> $profileSnapshot */
    public function setProfileSnapshot(array $profileSnapshot): static
    {
        $this->profileSnapshot = $profileSnapshot;

        return $this;
    }

    /** @return list<array<string, mixed>> */
    public function getRecommendationsSnapshot(): array
    {
        return $this->recommendationsSnapshot;
    }

    /** @param list<array<string, mixed>> $recommendationsSnapshot */
    public function setRecommendationsSnapshot(array $recommendationsSnapshot): static
    {
        $this->recommendationsSnapshot = $recommendationsSnapshot;

        return $this;
    }

    public function getPhotoFilename(): ?string
    {
        return $this->photoFilename;
    }

    public function setPhotoFilename(?string $photoFilename): static
    {
        $this->photoFilename = $photoFilename;

        return $this;
    }
}
