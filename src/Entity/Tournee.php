<?php

namespace App\Entity;

use App\Repository\TourneeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TourneeRepository::class)]
class Tournee
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, TourneeStop> */
    #[ORM\OneToMany(targetEntity: TourneeStop::class, mappedBy: 'tournee', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $stops;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->stops = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getDisplayName(): string
    {
        return $this->name ?: 'Tournée du ' . $this->date->format('d/m/Y');
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

    /** @return Collection<int, TourneeStop> */
    public function getStops(): Collection
    {
        return $this->stops;
    }

    public function addStop(TourneeStop $stop): static
    {
        if (!$this->stops->contains($stop)) {
            $this->stops->add($stop);
            $stop->setTournee($this);
        }
        return $this;
    }

    public function removeStop(TourneeStop $stop): static
    {
        if ($this->stops->removeElement($stop)) {
            if ($stop->getTournee() === $this) {
                $stop->setTournee(null);
            }
        }
        return $this;
    }

    public function isToday(): bool
    {
        return $this->date && $this->date->format('Y-m-d') === (new \DateTimeImmutable())->format('Y-m-d');
    }

    public function getTotalTravelTime(): int
    {
        $total = 0;
        foreach ($this->stops as $stop) {
            if ($stop->getAppointment() && $stop->getAppointment()->getTravelTime()) {
                $total += $stop->getAppointment()->getTravelTime();
            }
        }
        return $total;
    }
}
