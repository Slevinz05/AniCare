<?php

namespace App\Entity;

use App\Repository\AnimalShareRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AnimalShareRepository::class)]
class AnimalShare
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $sharedWithEmail = null;

    #[ORM\Column(length: 20)]
    private ?string $permissionLevel = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'animalShares')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Animal $animal = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSharedWithEmail(): ?string
    {
        return $this->sharedWithEmail;
    }

    public function setSharedWithEmail(string $sharedWithEmail): static
    {
        $this->sharedWithEmail = $sharedWithEmail;

        return $this;
    }

    public function getPermissionLevel(): ?string
    {
        return $this->permissionLevel;
    }

    public function setPermissionLevel(string $permissionLevel): static
    {
        $this->permissionLevel = $permissionLevel;

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

    public function getAnimal(): ?Animal
    {
        return $this->animal;
    }

    public function setAnimal(?Animal $animal): static
    {
        $this->animal = $animal;

        return $this;
    }
}
