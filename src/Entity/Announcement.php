<?php

namespace App\Entity;

use App\Repository\AnnouncementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AnnouncementRepository::class)]
#[ORM\Index(columns: ['is_active', 'sort_order'], name: 'idx_announcement_active_sort')]
class Announcement
{
    public const MEDIA_TYPE_NONE = 'none';
    public const MEDIA_TYPE_IMAGE = 'image';
    public const MEDIA_TYPE_VIDEO = 'video';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $body = null;

    #[ORM\Column(length: 20)]
    private string $mediaType = self::MEDIA_TYPE_NONE;

    #[ORM\Column(length: 1000, nullable: true)]
    private ?string $mediaUrl = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $ctaLabel = null;

    #[ORM\Column(length: 1000, nullable: true)]
    private ?string $ctaUrl = null;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private int $sortOrder = 0;

    #[ORM\Column]
    private bool $isBanner = false;

    #[ORM\Column]
    private int $delaySeconds = 0;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;
        $this->touch();
        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $body): static
    {
        $this->body = $body;
        $this->touch();
        return $this;
    }

    public function getMediaType(): string
    {
        return $this->mediaType;
    }

    public function setMediaType(string $mediaType): static
    {
        $this->mediaType = $mediaType;
        $this->touch();
        return $this;
    }

    public function getMediaUrl(): ?string
    {
        return $this->mediaUrl;
    }

    public function setMediaUrl(?string $mediaUrl): static
    {
        $this->mediaUrl = $mediaUrl;
        $this->touch();
        return $this;
    }

    public function getCtaLabel(): ?string
    {
        return $this->ctaLabel;
    }

    public function setCtaLabel(?string $ctaLabel): static
    {
        $this->ctaLabel = $ctaLabel;
        $this->touch();
        return $this;
    }

    public function getCtaUrl(): ?string
    {
        return $this->ctaUrl;
    }

    public function setCtaUrl(?string $ctaUrl): static
    {
        $this->ctaUrl = $ctaUrl;
        $this->touch();
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        $this->touch();
        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;
        $this->touch();
        return $this;
    }

    public function isBanner(): bool
    {
        return $this->isBanner;
    }

    public function setIsBanner(bool $isBanner): static
    {
        $this->isBanner = $isBanner;
        $this->touch();
        return $this;
    }

    public function getDelaySeconds(): int
    {
        return $this->delaySeconds;
    }

    public function setDelaySeconds(int $delaySeconds): static
    {
        $this->delaySeconds = max(0, $delaySeconds);
        $this->touch();
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getMediaTypeLabel(): string
    {
        return match ($this->mediaType) {
            self::MEDIA_TYPE_IMAGE => 'Rasm',
            self::MEDIA_TYPE_VIDEO => 'Video',
            default => 'Media yo\'q',
        };
    }

    public function hasMedia(): bool
    {
        return $this->mediaUrl !== null && $this->mediaUrl !== '' && $this->mediaType !== self::MEDIA_TYPE_NONE;
    }

    public function getEmbedUrl(): ?string
    {
        if (!$this->hasMedia()) {
            return null;
        }

        if ($this->mediaType === self::MEDIA_TYPE_IMAGE) {
            return $this->mediaUrl;
        }

        $url = $this->mediaUrl ?? '';

        if (str_contains($url, 'youtube.com/watch?v=')) {
            return preg_replace('/^.*youtube\.com\/watch\?v=([^&]+).*$/', 'https://www.youtube.com/embed/$1', $url) ?: $url;
        }

        if (str_contains($url, 'youtu.be/')) {
            return preg_replace('/^.*youtu\.be\/([^?&]+).*$/', 'https://www.youtube.com/embed/$1', $url) ?: $url;
        }

        if (str_contains($url, 'vimeo.com/')) {
            return preg_replace('/^.*vimeo\.com\/(\d+).*$/', 'https://player.vimeo.com/video/$1', $url) ?: $url;
        }

        return $url;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
