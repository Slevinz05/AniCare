<?php

namespace App\Entity;

use App\Repository\HealthBookEntryRepository;
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

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

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

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $batchNumber = null;

    #[ORM\ManyToOne(inversedBy: 'healthBookEntries')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Animal $animal = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $documents = [];

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
}
