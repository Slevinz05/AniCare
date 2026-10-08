<?php

namespace App\Entity;

use App\Repository\AnimalReferentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AnimalReferentRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_animal_user_type', fields: ['animal', 'user', 'type'])]
class AnimalReferent
{
    public const TYPE_PRINCIPAL = 'principal';
    public const TYPE_SECONDAIRE = 'secondaire';

    public const ROLE_PROPRIETAIRE = 'proprietaire';
    public const ROLE_CAVALIER = 'cavalier';
    public const ROLE_ENTRAINEUR = 'entraineur';
    public const ROLE_GERANT = 'gerant';
    public const ROLE_GROOM = 'groom';
    public const ROLE_ELEVEUR = 'eleveur';
    public const ROLE_AUTRE = 'autre';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_PENDING = 'pending';
    public const STATUS_REVOKED = 'revoked';
    public const STATUS_REFUSED = 'refused';

    public const TYPES = [self::TYPE_PRINCIPAL, self::TYPE_SECONDAIRE];
    public const ROLES = [
        self::ROLE_PROPRIETAIRE,
        self::ROLE_CAVALIER,
        self::ROLE_ENTRAINEUR,
        self::ROLE_GERANT,
        self::ROLE_GROOM,
        self::ROLE_ELEVEUR,
        self::ROLE_AUTRE,
    ];
    public const STATUSES = [self::STATUS_ACTIVE, self::STATUS_PENDING, self::STATUS_REVOKED, self::STATUS_REFUSED];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'referents')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Animal $animal = null;

    #[ORM\ManyToOne(inversedBy: 'animalReferents')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    #[ORM\Column(length: 20)]
    private string $type = self::TYPE_PRINCIPAL;

    #[ORM\Column(length: 20)]
    private string $role = self::ROLE_PROPRIETAIRE;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_ACTIVE;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $designatedBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $designatedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $revokedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contactName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contactEmail = null;

    #[ORM\Column(length: 64, nullable: true, unique: true)]
    private ?string $invitationToken = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $canShare = false;

    public function __construct()
    {
        $this->designatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAnimal(): ?Animal
    {
        return $this->animal;
    }

    public function setAnimal(?Animal $animal): static
    {
        $this->animal = $animal;
        return $this;
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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function isPrincipal(): bool
    {
        return $this->type === self::TYPE_PRINCIPAL;
    }

    public function isSecondaire(): bool
    {
        return $this->type === self::TYPE_SECONDAIRE;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;
        return $this;
    }

    public function getRoleLabel(): string
    {
        return match ($this->role) {
            self::ROLE_PROPRIETAIRE => 'Propriétaire',
            self::ROLE_CAVALIER => 'Cavalier',
            self::ROLE_ENTRAINEUR => 'Entraîneur',
            self::ROLE_GERANT => 'Gérant',
            self::ROLE_GROOM => 'Groom',
            self::ROLE_ELEVEUR => 'Éleveur',
            self::ROLE_AUTRE => 'Autre',
            default => $this->role,
        };
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

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getDesignatedBy(): ?User
    {
        return $this->designatedBy;
    }

    public function setDesignatedBy(?User $designatedBy): static
    {
        $this->designatedBy = $designatedBy;
        return $this;
    }

    public function getDesignatedAt(): \DateTimeImmutable
    {
        return $this->designatedAt;
    }

    public function setDesignatedAt(\DateTimeImmutable $designatedAt): static
    {
        $this->designatedAt = $designatedAt;
        return $this;
    }

    public function getRevokedAt(): ?\DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function revoke(): static
    {
        $this->status = self::STATUS_REVOKED;
        $this->revokedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getContactName(): ?string
    {
        return $this->contactName;
    }

    public function setContactName(?string $contactName): static
    {
        $this->contactName = $contactName;
        return $this;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(?string $contactEmail): static
    {
        $this->contactEmail = $contactEmail;
        return $this;
    }

    public function getInvitationToken(): ?string
    {
        return $this->invitationToken;
    }

    public function setInvitationToken(?string $invitationToken): static
    {
        $this->invitationToken = $invitationToken;
        return $this;
    }

    public function isExternalInvitation(): bool
    {
        return $this->user === null && $this->contactEmail !== null;
    }

    public function canShare(): bool
    {
        if ($this->isPrincipal() && $this->isActive()) {
            return true;
        }

        return $this->isSecondaire() && $this->isActive() && $this->canShare;
    }

    public function getCanShare(): bool
    {
        return $this->canShare;
    }

    public function setCanShare(bool $canShare): static
    {
        $this->canShare = $canShare;
        return $this;
    }
}
