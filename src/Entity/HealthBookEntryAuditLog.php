<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Index(columns: ['entry_id'], name: 'idx_audit_entry')]
#[ORM\Index(columns: ['action'], name: 'idx_audit_action')]
class HealthBookEntryAuditLog
{
    public const ACTION_CREATED_DRAFT = 'created_draft';
    public const ACTION_CREATED_PUBLISHED = 'created_published';
    public const ACTION_PUBLISHED = 'published';
    public const ACTION_CORRECTED = 'corrected';
    public const ACTION_SHARED = 'shared';
    public const ACTION_SHARE_REVOKED = 'share_revoked';
    public const ACTION_ARCHIVED = 'archived';
    public const ACTION_DELETED = 'deleted';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?HealthBookEntry $entry = null;

    #[ORM\Column(length: 30)]
    private string $action;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $performedBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $performedAt;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $details = null;

    public function __construct(HealthBookEntry $entry, string $action, User $performedBy, ?array $details = null)
    {
        $this->entry = $entry;
        $this->action = $action;
        $this->performedBy = $performedBy;
        $this->performedAt = new \DateTimeImmutable();
        $this->details = $details;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEntry(): ?HealthBookEntry
    {
        return $this->entry;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getActionLabel(): string
    {
        return match ($this->action) {
            self::ACTION_CREATED_DRAFT => 'Brouillon créé',
            self::ACTION_CREATED_PUBLISHED => 'Consultation créée et publiée',
            self::ACTION_PUBLISHED => 'Brouillon validé',
            self::ACTION_CORRECTED => 'Correction apportée',
            self::ACTION_SHARED => 'Partage effectué',
            self::ACTION_SHARE_REVOKED => 'Partage révoqué',
            self::ACTION_ARCHIVED => 'Consultation archivée',
            self::ACTION_DELETED => 'Brouillon supprimé',
            default => $this->action,
        };
    }

    public function getPerformedBy(): ?User
    {
        return $this->performedBy;
    }

    public function getPerformedAt(): \DateTimeImmutable
    {
        return $this->performedAt;
    }

    public function getDetails(): ?array
    {
        return $this->details;
    }
}
