<?php

namespace App\Entity;

use App\Repository\AppointmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AppointmentRepository::class)]
class Appointment
{
    public const TYPE_APPOINTMENT = 'appointment';
    public const TYPE_REST = 'rest';
    public const TYPE_PERSONAL = 'personal';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    private string $eventType = self::TYPE_APPOINTMENT;

    #[ORM\Column(length: 255)]
    private ?string $reason = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $scheduledAt = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private int $duration = 60;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $travelTime = null;

    #[ORM\Column(length: 30)]
    private ?string $status = 'PENDING';

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $consultationType = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $publicNotes = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $privateNotes = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $client = null;

    /**
     * @var Collection<int, Animal>
     */
    #[ORM\ManyToMany(targetEntity: Animal::class)]
    #[ORM\JoinTable(name: 'appointment_animal')]
    private Collection $animals;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $sharedWithProfessional = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    // Keep for backward compat with existing data
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Animal $animal = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    /**
     * @var Collection<int, HealthBookEntry>
     */
    #[ORM\OneToMany(targetEntity: HealthBookEntry::class, mappedBy: 'appointment')]
    private Collection $healthBookEntries;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->animals = new ArrayCollection();
        $this->healthBookEntries = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function setEventType(string $eventType): static
    {
        $this->eventType = $eventType;
        return $this;
    }

    public function getEventTypeLabel(): string
    {
        return match ($this->eventType) {
            self::TYPE_APPOINTMENT => 'RDV Pro',
            self::TYPE_REST => 'Temps de repos',
            self::TYPE_PERSONAL => 'Rendez-vous personnel',
            default => $this->eventType,
        };
    }

    public function getEventTypeShortLabel(): string
    {
        return match ($this->eventType) {
            self::TYPE_APPOINTMENT => 'Pro',
            self::TYPE_REST => 'Repos',
            self::TYPE_PERSONAL => 'Perso',
            default => $this->eventType,
        };
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(string $reason): static
    {
        $this->reason = $reason;
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

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): static
    {
        $this->duration = $duration;
        return $this;
    }

    public function getTravelTime(): ?int
    {
        return $this->travelTime;
    }

    public function setTravelTime(?int $travelTime): static
    {
        $this->travelTime = $travelTime;
        return $this;
    }

    public function getEndAt(): ?\DateTimeImmutable
    {
        if (!$this->scheduledAt) {
            return null;
        }

        return $this->scheduledAt->modify("+{$this->duration} minutes");
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Sans client, personne d'autre n'a à confirmer : le RDV est confirmé d'office.
     * Si un client est ajouté à un RDV confirmé d'office, il repasse en attente de sa confirmation.
     */
    public function syncStatusWithClient(?User $previousClient = null): static
    {
        if ($this->status === 'CANCELLED') {
            return $this;
        }

        if ($this->client === null) {
            $this->status = 'CONFIRMED';
        } elseif ($this->client !== $previousClient) {
            $this->status = 'PENDING';
        }

        return $this;
    }

    public function getConsultationType(): ?string
    {
        return $this->consultationType;
    }

    public function setConsultationType(?string $consultationType): static
    {
        $this->consultationType = $consultationType;
        return $this;
    }

    public function getPublicNotes(): ?string
    {
        return $this->publicNotes;
    }

    public function setPublicNotes(?string $publicNotes): static
    {
        $this->publicNotes = $publicNotes;
        return $this;
    }

    public function getPrivateNotes(): ?string
    {
        return $this->privateNotes;
    }

    public function setPrivateNotes(?string $privateNotes): static
    {
        $this->privateNotes = $privateNotes;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;
        return $this;
    }

    public function getClient(): ?User
    {
        return $this->client;
    }

    public function setClient(?User $client): static
    {
        $this->client = $client;
        return $this;
    }

    /**
     * @return Collection<int, Animal>
     */
    public function getAnimals(): Collection
    {
        return $this->animals;
    }

    public function addAnimal(Animal $animal): static
    {
        if (!$this->animals->contains($animal)) {
            $this->animals->add($animal);
        }
        return $this;
    }

    public function removeAnimal(Animal $animal): static
    {
        $this->animals->removeElement($animal);
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

    public function getSharedWithProfessional(): ?User
    {
        return $this->sharedWithProfessional;
    }

    public function setSharedWithProfessional(?User $sharedWithProfessional): static
    {
        $this->sharedWithProfessional = $sharedWithProfessional;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isPast(): bool
    {
        return $this->scheduledAt < new \DateTimeImmutable();
    }

    // Legacy compat
    public function getAnimal(): ?Animal
    {
        return $this->animal ?? $this->animals->first() ?: null;
    }

    public function setAnimal(?Animal $animal): static
    {
        $this->animal = $animal;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes ?? $this->publicNotes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    /**
     * @return Collection<int, HealthBookEntry>
     */
    public function getHealthBookEntries(): Collection
    {
        return $this->healthBookEntries;
    }
}
