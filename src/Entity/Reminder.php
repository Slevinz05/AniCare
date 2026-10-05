<?php

namespace App\Entity;

use App\Repository\ReminderRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReminderRepository::class)]
class Reminder
{
    public const CATEGORY_VACCINE = 'vaccine';
    public const CATEGORY_VERMIFUGE = 'vermifuge';
    public const CATEGORY_DENTAIRE = 'dentaire';
    public const CATEGORY_MARECHALERIE = 'marechalerie';
    public const CATEGORY_OSTEO = 'osteo';
    public const CATEGORY_CONTROLE = 'controle';
    public const CATEGORY_AUTRE = 'autre';

    public const CATEGORIES = [
        self::CATEGORY_VACCINE => 'Vaccin',
        self::CATEGORY_VERMIFUGE => 'Vermifuge',
        self::CATEGORY_DENTAIRE => 'Dentaire',
        self::CATEGORY_MARECHALERIE => 'Maréchalerie',
        self::CATEGORY_OSTEO => 'Ostéopathie',
        self::CATEGORY_CONTROLE => 'Contrôle',
        self::CATEGORY_AUTRE => 'Autre',
    ];

    public const RECURRENCES = [
        'weekly' => 'Hebdomadaire',
        'monthly' => 'Mensuel',
        'quarterly' => 'Trimestriel',
        'biannual' => 'Semestriel',
        'annual' => 'Annuel',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $scheduledAt = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $recurrence = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $nextOccurrence = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Animal $animal = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $createdBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private bool $active = true;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getCategoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category ?? 'Autre';
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    public function getScheduledAt(): ?\DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(\DateTimeImmutable $scheduledAt): static
    {
        $this->scheduledAt = $scheduledAt;
        return $this;
    }

    public function getRecurrence(): ?string
    {
        return $this->recurrence;
    }

    public function setRecurrence(?string $recurrence): static
    {
        $this->recurrence = $recurrence;
        return $this;
    }

    public function getRecurrenceLabel(): string
    {
        return self::RECURRENCES[$this->recurrence] ?? 'Ponctuel';
    }

    public function getNextOccurrence(): ?\DateTimeImmutable
    {
        return $this->nextOccurrence;
    }

    public function setNextOccurrence(?\DateTimeImmutable $nextOccurrence): static
    {
        $this->nextOccurrence = $nextOccurrence;
        return $this;
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

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;
        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;
        return $this;
    }

    public function isOverdue(): bool
    {
        $date = $this->nextOccurrence ?? $this->scheduledAt;
        return $date && $date < new \DateTimeImmutable('today');
    }

    public function isDueToday(): bool
    {
        $date = $this->nextOccurrence ?? $this->scheduledAt;
        if (!$date) {
            return false;
        }
        $today = new \DateTimeImmutable('today');
        return $date->format('Y-m-d') === $today->format('Y-m-d');
    }

    public function isDueSoon(int $days = 7): bool
    {
        $date = $this->nextOccurrence ?? $this->scheduledAt;
        if (!$date) {
            return false;
        }
        $limit = new \DateTimeImmutable("+{$days} days");
        return $date <= $limit && !$this->isOverdue() && !$this->isDueToday();
    }

    public function computeNextOccurrence(): void
    {
        if (!$this->recurrence || !$this->scheduledAt) {
            $this->nextOccurrence = $this->scheduledAt;
            return;
        }

        $now = new \DateTimeImmutable();
        $next = $this->scheduledAt;

        while ($next < $now) {
            $next = match ($this->recurrence) {
                'weekly' => $next->modify('+1 week'),
                'monthly' => $next->modify('+1 month'),
                'quarterly' => $next->modify('+3 months'),
                'biannual' => $next->modify('+6 months'),
                'annual' => $next->modify('+1 year'),
                default => $next,
            };
        }

        $this->nextOccurrence = $next;
    }
}
