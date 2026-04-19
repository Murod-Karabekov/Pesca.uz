<?php

namespace App\Entity;

use App\Repository\CorporatePartnershipRequestRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CorporatePartnershipRequestRepository::class)]
#[ORM\Table(name: 'corporate_partnership_request')]
#[ORM\Index(name: 'idx_corporate_created', columns: ['created_at'])]
class CorporatePartnershipRequest
{
    public const STATUS_PENDING = 'pending';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $submittedByUser = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Tashkilot nomini kiriting.')]
    #[Assert\Length(max: 255)]
    private ?string $organizationName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Mas\'ul shaxs ism-familiyasini kiriting.')]
    #[Assert\Length(max: 255)]
    private ?string $contactFullName = null;

    #[ORM\Column(length: 30)]
    #[Assert\NotBlank(message: 'Telefon raqamini kiriting.')]
    #[Assert\Length(max: 30)]
    private ?string $phone = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Manzilni kiriting.')]
    private ?string $address = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Hamkorlik bo‘yicha takliflaringizni yozing.')]
    private ?string $additionalNotes = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubmittedByUser(): ?User
    {
        return $this->submittedByUser;
    }

    public function setSubmittedByUser(?User $submittedByUser): static
    {
        $this->submittedByUser = $submittedByUser;

        return $this;
    }

    public function getOrganizationName(): ?string
    {
        return $this->organizationName;
    }

    public function setOrganizationName(string $organizationName): static
    {
        $this->organizationName = $organizationName;

        return $this;
    }

    public function getContactFullName(): ?string
    {
        return $this->contactFullName;
    }

    public function setContactFullName(string $contactFullName): static
    {
        $this->contactFullName = $contactFullName;

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

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getAdditionalNotes(): ?string
    {
        return $this->additionalNotes;
    }

    public function setAdditionalNotes(string $additionalNotes): static
    {
        $this->additionalNotes = $additionalNotes;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
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
}
