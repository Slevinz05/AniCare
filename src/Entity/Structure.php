<?php

namespace App\Entity;

use App\Repository\StructureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: StructureRepository::class)]
class Structure
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Veuillez saisir le nom de la structure.')]
    private ?string $name = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $street = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $complement = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $postalCode = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $country = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $siret = null;

    #[ORM\Column(length: 8, unique: true)]
    private ?string $claimCode = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    /** @var Collection<int, StructureMembership> */
    #[ORM\OneToMany(targetEntity: StructureMembership::class, mappedBy: 'structure', orphanRemoval: true)]
    private Collection $memberships;

    /** @var Collection<int, Animal> */
    #[ORM\OneToMany(targetEntity: Animal::class, mappedBy: 'structure')]
    private Collection $animals;

    public function __construct()
    {
        $this->memberships = new ArrayCollection();
        $this->animals = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->claimCode = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(?string $street): static
    {
        $this->street = $street;
        return $this;
    }

    public function getComplement(): ?string
    {
        return $this->complement;
    }

    public function setComplement(?string $complement): static
    {
        $this->complement = $complement;
        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): static
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): static
    {
        $this->country = $country;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function setSiret(?string $siret): static
    {
        $this->siret = $siret;
        return $this;
    }

    public function getClaimCode(): ?string
    {
        return $this->claimCode;
    }

    public function setClaimCode(string $claimCode): static
    {
        $this->claimCode = $claimCode;
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /** @return Collection<int, StructureMembership> */
    public function getMemberships(): Collection
    {
        return $this->memberships;
    }

    public function addMembership(StructureMembership $membership): static
    {
        if (!$this->memberships->contains($membership)) {
            $this->memberships->add($membership);
            $membership->setStructure($this);
        }
        return $this;
    }

    public function removeMembership(StructureMembership $membership): static
    {
        if ($this->memberships->removeElement($membership)) {
            if ($membership->getStructure() === $this) {
                $membership->setStructure(null);
            }
        }
        return $this;
    }

    /** @return Collection<int, Animal> */
    public function getAnimals(): Collection
    {
        return $this->animals;
    }

    public function getManager(): ?User
    {
        foreach ($this->memberships as $m) {
            if ($m->getRole() === StructureMembership::ROLE_MANAGER) {
                return $m->getUser();
            }
        }
        return null;
    }

    public function hasManager(): bool
    {
        return $this->getManager() !== null;
    }

    public function getFullAddress(): string
    {
        return implode(', ', array_filter([
            $this->street,
            $this->complement,
            trim(($this->postalCode ?? '') . ' ' . ($this->city ?? '')),
            $this->country,
        ]));
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
