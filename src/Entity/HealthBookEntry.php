<?php

namespace App\Entity;

use App\Repository\HealthBookEntryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HealthBookEntryRepository::class)]
class HealthBookEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $anamnesis = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $staticExamination = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $rehabilitationType = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $rehabilitation = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $colleagueRecommendation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $veterinarianName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $dosage = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $frequency = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $nextReminderAt = null;

    #[ORM\Column(nullable: true)]
    private ?int $recurrenceMonths = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $recurrenceType = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $batchNumber = null;

    #[ORM\ManyToOne(inversedBy: 'healthBookEntries')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Animal $animal = null;

    #[ORM\ManyToOne]
    private ?User $veterinarian = null;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    public const ALLOWED_TRANSITIONS = [
        self::STATUS_DRAFT => [self::STATUS_PUBLISHED],
        self::STATUS_PUBLISHED => [self::STATUS_ARCHIVED],
        self::STATUS_ARCHIVED => [],
    ];

    #[ORM\Column(length: 20, options: ['default' => 'published'])]
    private string $status = 'published';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $archivedAt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $archivedBy = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $archiveReason = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $documents = [];

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $anatomicalLocations = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'healthBookEntries')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Appointment $appointment = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $createdBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $sharedAt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $sharedWithUser = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $sharedWithEmail = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Structure $sharedWithStructure = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $shareMode = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $shareStructureMode = null;

    #[ORM\Column(nullable: true)]
    private ?int $behaviorScore = null;

    #[ORM\Column(nullable: true)]
    private ?int $bodyConditionScore = null;

    #[ORM\Column(nullable: true)]
    private ?int $workDone = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $correctedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $correctionReason = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $lastCorrectedBy = null;

    #[ORM\Column(type: 'integer', options: ['default' => 1])]
    private int $version = 1;

    /** @var Collection<int, HealthBookEntryShare> */
    #[ORM\OneToMany(targetEntity: HealthBookEntryShare::class, mappedBy: 'entry', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $shares;

    public function __construct()
    {
        $this->shares = new ArrayCollection();
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getAnamnesis(): ?string
    {
        return $this->anamnesis;
    }

    public function setAnamnesis(?string $anamnesis): static
    {
        $this->anamnesis = $anamnesis;

        return $this;
    }

    public function getStaticExamination(): ?string
    {
        return $this->staticExamination;
    }

    public function setStaticExamination(?string $staticExamination): static
    {
        $this->staticExamination = $staticExamination;

        return $this;
    }

    public function getRehabilitationType(): ?string
    {
        return $this->rehabilitationType;
    }

    public function setRehabilitationType(?string $rehabilitationType): static
    {
        $this->rehabilitationType = $rehabilitationType;

        return $this;
    }

    public function getRehabilitation(): ?string
    {
        return $this->rehabilitation;
    }

    public function setRehabilitation(?string $rehabilitation): static
    {
        $this->rehabilitation = $rehabilitation;

        return $this;
    }

    public function getColleagueRecommendation(): ?string
    {
        return $this->colleagueRecommendation;
    }

    public function setColleagueRecommendation(?string $colleagueRecommendation): static
    {
        $this->colleagueRecommendation = $colleagueRecommendation;

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

    public function getVeterinarianName(): ?string
    {
        return $this->veterinarianName;
    }

    public function setVeterinarianName(?string $veterinarianName): static
    {
        $this->veterinarianName = $veterinarianName;

        return $this;
    }

    public function getVeterinarian(): ?User
    {
        return $this->veterinarian;
    }

    public function setVeterinarian(?User $veterinarian): static
    {
        $this->veterinarian = $veterinarian;

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

    public function getDosage(): ?string
    {
        return $this->dosage;
    }

    public function setDosage(?string $dosage): static
    {
        $this->dosage = $dosage;

        return $this;
    }

    public function getFrequency(): ?string
    {
        return $this->frequency;
    }

    public function setFrequency(?string $frequency): static
    {
        $this->frequency = $frequency;

        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getNextReminderAt(): ?\DateTimeImmutable
    {
        return $this->nextReminderAt;
    }

    public function setNextReminderAt(?\DateTimeImmutable $nextReminderAt): static
    {
        $this->nextReminderAt = $nextReminderAt;

        return $this;
    }

    public function getRecurrenceMonths(): ?int
    {
        return $this->recurrenceMonths;
    }

    public function setRecurrenceMonths(?int $recurrenceMonths): static
    {
        $this->recurrenceMonths = $recurrenceMonths;

        if ($recurrenceMonths && $this->date) {
            $this->nextReminderAt = $this->date->modify("+{$recurrenceMonths} months");
        }

        return $this;
    }

    public function getRecurrenceType(): ?string
    {
        return $this->recurrenceType;
    }

    public function setRecurrenceType(?string $recurrenceType): static
    {
        $this->recurrenceType = $recurrenceType;

        if ($recurrenceType && $this->date) {
            $this->nextReminderAt = match ($recurrenceType) {
                'daily' => $this->date->modify('+1 day'),
                'weekly' => $this->date->modify('+1 week'),
                'bimonthly' => $this->date->modify('+2 weeks'),
                'monthly' => $this->date->modify('+1 month'),
                'annual' => $this->date->modify('+1 year'),
                default => $this->nextReminderAt,
            };
        }

        return $this;
    }

    public function getRecurrenceLabel(): ?string
    {
        return match ($this->recurrenceType) {
            'daily' => 'Journalier',
            'weekly' => 'Hebdomadaire',
            'bimonthly' => 'Bimensuel',
            'monthly' => 'Mensuel',
            'annual' => 'Annuel',
            default => null,
        };
    }

    public function getBatchNumber(): ?string
    {
        return $this->batchNumber;
    }

    public function setBatchNumber(?string $batchNumber): static
    {
        $this->batchNumber = $batchNumber;

        return $this;
    }

    public function isActiveTreatment(): bool
    {
        if (!in_array($this->type, ['Traitement', 'Antiparasitaire'], true)) {
            return false;
        }

        $today = new \DateTimeImmutable('today');
        return $this->date <= $today && ($this->endDate === null || $this->endDate >= $today);
    }

    public function isOverdueReminder(): bool
    {
        return $this->nextReminderAt !== null && $this->nextReminderAt < new \DateTimeImmutable('today');
    }

    public function isDueSoon(int $days = 30): bool
    {
        if ($this->nextReminderAt === null) {
            return false;
        }

        $today = new \DateTimeImmutable('today');
        $threshold = $today->modify("+{$days} days");

        return $this->nextReminderAt >= $today && $this->nextReminderAt <= $threshold;
    }

    public function getDocuments(): array
    {
        return $this->documents ?? [];
    }

    public function setDocuments(?array $documents): static
    {
        $this->documents = $documents ?? [];

        return $this;
    }

    public function addDocument(array $document): static
    {
        $documents = $this->getDocuments();
        $documents[] = $document;

        $this->documents = $documents;

        return $this;
    }

    public function removeDocument(string $fileName): static
    {
        $this->documents = array_values(array_filter(
            $this->getDocuments(),
            fn(array $document) => ($document['fileName'] ?? null) !== $fileName
        ));

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED;
    }

    public function isShared(): bool
    {
        return $this->shares->count() > 0;
    }

    public function canTransitionTo(string $targetStatus): bool
    {
        return in_array($targetStatus, self::ALLOWED_TRANSITIONS[$this->status] ?? [], true);
    }

    public function initAsDraft(): static
    {
        $this->status = self::STATUS_DRAFT;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function initAsPublished(): static
    {
        $this->status = self::STATUS_PUBLISHED;
        $this->publishedAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function publish(): static
    {
        if (!$this->isDraft()) {
            throw new \LogicException('Seul un brouillon peut être publié.');
        }
        $this->status = self::STATUS_PUBLISHED;
        $this->publishedAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function archive(User $archivedBy, ?string $reason = null): static
    {
        if (!$this->isPublished()) {
            throw new \LogicException('Seule une consultation publiée peut être archivée.');
        }
        $this->status = self::STATUS_ARCHIVED;
        $this->archivedAt = new \DateTimeImmutable();
        $this->archivedBy = $archivedBy;
        $this->archiveReason = $reason;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function canBeDeleted(): bool
    {
        return $this->isDraft();
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function getArchivedAt(): ?\DateTimeImmutable
    {
        return $this->archivedAt;
    }

    public function getArchivedBy(): ?User
    {
        return $this->archivedBy;
    }

    public function getArchiveReason(): ?string
    {
        return $this->archiveReason;
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

    public function getSharedAt(): ?\DateTimeImmutable
    {
        return $this->sharedAt;
    }

    public function setSharedAt(?\DateTimeImmutable $sharedAt): static
    {
        $this->sharedAt = $sharedAt;

        return $this;
    }

    public function isAuthor(User $user): bool
    {
        return $this->createdBy === $user || $this->veterinarian === $user;
    }

    public function getAnatomicalLocations(): array
    {
        return $this->anatomicalLocations ?? [];
    }

    public function setAnatomicalLocations(?array $anatomicalLocations): static
    {
        $this->anatomicalLocations = $anatomicalLocations ?? [];

        return $this;
    }

    public function getAppointment(): ?Appointment
    {
        return $this->appointment;
    }

    public function setAppointment(?Appointment $appointment): static
    {
        $this->appointment = $appointment;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getSharedWithUser(): ?User
    {
        return $this->sharedWithUser;
    }

    public function setSharedWithUser(?User $sharedWithUser): static
    {
        $this->sharedWithUser = $sharedWithUser;
        return $this;
    }

    public function getSharedWithEmail(): ?string
    {
        return $this->sharedWithEmail;
    }

    public function setSharedWithEmail(?string $sharedWithEmail): static
    {
        $this->sharedWithEmail = $sharedWithEmail;
        return $this;
    }

    public function getSharedWithStructure(): ?Structure
    {
        return $this->sharedWithStructure;
    }

    public function setSharedWithStructure(?Structure $sharedWithStructure): static
    {
        $this->sharedWithStructure = $sharedWithStructure;
        return $this;
    }

    public function getShareMode(): ?string
    {
        return $this->shareMode;
    }

    public function setShareMode(?string $shareMode): static
    {
        $this->shareMode = $shareMode;
        return $this;
    }

    public function getShareStructureMode(): ?string
    {
        return $this->shareStructureMode;
    }

    public function setShareStructureMode(?string $shareStructureMode): static
    {
        $this->shareStructureMode = $shareStructureMode;
        return $this;
    }

    /** @return Collection<int, HealthBookEntryShare> */
    public function getShares(): Collection
    {
        return $this->shares;
    }

    public function addShare(HealthBookEntryShare $share): static
    {
        if (!$this->shares->contains($share)) {
            $this->shares->add($share);
            $share->setEntry($this);
        }
        return $this;
    }

    public function removeShare(HealthBookEntryShare $share): static
    {
        $this->shares->removeElement($share);
        return $this;
    }

    public function getShareModeFor(User $user): ?string
    {
        foreach ($this->shares as $share) {
            if ($share->getSharedWithUser() === $user) {
                return $share->getMode();
            }
            if ($share->getSharedWithEmail() === $user->getEmail()) {
                return $share->getMode();
            }
            if ($share->getSharedWithStructure()) {
                foreach ($share->getSharedWithStructure()->getMemberships() as $membership) {
                    if ($membership->getUser() === $user) {
                        return $share->getMode();
                    }
                }
            }
        }

        // Fallback: anciens champs (données pré-migration)
        if ($this->sharedWithUser === $user || $this->sharedWithEmail === $user->getEmail()) {
            return $this->shareMode ?? 'readonly';
        }
        if ($this->sharedWithStructure) {
            foreach ($this->sharedWithStructure->getMemberships() as $membership) {
                if ($membership->getUser() === $user) {
                    return $this->shareStructureMode ?? 'summary';
                }
            }
        }

        return null;
    }

    public function getBehaviorScore(): ?int
    {
        return $this->behaviorScore;
    }

    public function setBehaviorScore(?int $behaviorScore): static
    {
        $this->behaviorScore = $behaviorScore;
        return $this;
    }

    public function getBodyConditionScore(): ?int
    {
        return $this->bodyConditionScore;
    }

    public function setBodyConditionScore(?int $bodyConditionScore): static
    {
        $this->bodyConditionScore = $bodyConditionScore;
        return $this;
    }

    public function getWorkDone(): ?int
    {
        return $this->workDone;
    }

    public function setWorkDone(?int $workDone): static
    {
        $this->workDone = $workDone;
        return $this;
    }

    public function getCorrectedAt(): ?\DateTimeImmutable
    {
        return $this->correctedAt;
    }

    public function getCorrectionReason(): ?string
    {
        return $this->correctionReason;
    }

    public function getLastCorrectedBy(): ?User
    {
        return $this->lastCorrectedBy;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function applyCorrection(User $correctedBy, ?string $reason = null): static
    {
        $this->version++;
        $this->correctedAt = new \DateTimeImmutable();
        $this->lastCorrectedBy = $correctedBy;
        $this->correctionReason = $reason;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function canBeSharedBy(User $user): bool
    {
        if ($this->isAuthor($user)) {
            return true;
        }

        $animal = $this->getAnimal();
        if (!$animal) {
            return false;
        }

        foreach ($animal->getReferents() as $referent) {
            if ($referent->getUser() === $user && $referent->canShare()) {
                return true;
            }
        }

        return false;
    }
}
