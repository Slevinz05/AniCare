<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class HealthBookEntryShare
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'shares')]
    #[ORM\JoinColumn(nullable: false)]
    private ?HealthBookEntry $entry = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $sharedWithUser = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $sharedWithEmail = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Structure $sharedWithStructure = null;

    #[ORM\Column(length: 20)]
    private string $mode = 'readonly';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEntry(): ?HealthBookEntry
    {
        return $this->entry;
    }

    public function setEntry(?HealthBookEntry $entry): static
    {
        $this->entry = $entry;
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

    public function getMode(): string
    {
        return $this->mode;
    }

    public function setMode(string $mode): static
    {
        $this->mode = $mode;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getTargetLabel(): string
    {
        if ($this->sharedWithUser) {
            return $this->sharedWithUser->getFullName();
        }
        if ($this->sharedWithStructure) {
            return $this->sharedWithStructure->getName();
        }
        return $this->sharedWithEmail ?? '';
    }

    public function getModeLabel(): string
    {
        return match ($this->mode) {
            'summary' => 'Résumé',
            default => 'Lecture seule',
        };
    }
}
