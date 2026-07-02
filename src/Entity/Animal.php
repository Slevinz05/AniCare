<?php

namespace App\Entity;

use App\Repository\AnimalRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AnimalRepository::class)]
class Animal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 100)]
    private ?string $species = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $breed = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $birthDate = null;

    #[ORM\Column(length: 10)]
    private ?string $gender = null;

    #[ORM\Column(nullable: true)]
    private ?float $weight = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $bloodType = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $identificationNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $documents = [];

    #[ORM\ManyToOne(inversedBy: 'animals')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    /** @var Collection<int, HealthBookEntry> */
    #[ORM\OneToMany(targetEntity: HealthBookEntry::class, mappedBy: 'animal')]
    private Collection $healthBookEntries;

    /** @var Collection<int, AnimalShare> */
    #[ORM\OneToMany(targetEntity: AnimalShare::class, mappedBy: 'animal')]
    private Collection $animalShares;

    /** @var Collection<int, WeightRecord> */
    #[ORM\OneToMany(targetEntity: WeightRecord::class, mappedBy: 'animal', orphanRemoval: true)]
    private Collection $weightRecords;

    /** @var Collection<int, Allergy> */
    #[ORM\OneToMany(targetEntity: Allergy::class, mappedBy: 'animal', orphanRemoval: true)]
    private Collection $allergies;

    public function __construct()
    {
        $this->healthBookEntries = new ArrayCollection();
        $this->animalShares = new ArrayCollection();
        $this->weightRecords = new ArrayCollection();
        $this->allergies = new ArrayCollection();
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

    public function getSpecies(): ?string
    {
        return $this->species;
    }

    public function setSpecies(string $species): static
    {
        $this->species = $species;

        return $this;
    }

    public function getBreed(): ?string
    {
        return $this->breed;
    }

    public function setBreed(?string $breed): static
    {
        $this->breed = $breed;

        return $this;
    }

    public function getBirthDate(): ?\DateTimeImmutable
    {
        return $this->birthDate;
    }

    public function setBirthDate(?\DateTimeImmutable $birthDate): static
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(string $gender): static
    {
        $this->gender = $gender;

        return $this;
    }

    public function getWeight(): ?float
    {
        return $this->weight;
    }

    public function setWeight(?float $weight): static
    {
        $this->weight = $weight;

        return $this;
    }

    public function getBloodType(): ?string
    {
        return $this->bloodType;
    }

    public function setBloodType(?string $bloodType): static
    {
        $this->bloodType = $bloodType;

        return $this;
    }

    public function getIdentificationNumber(): ?string
    {
        return $this->identificationNumber;
    }

    public function setIdentificationNumber(?string $identificationNumber): static
    {
        $this->identificationNumber = $identificationNumber;

        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;

        return $this;
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

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    /** @return Collection<int, HealthBookEntry> */
    public function getHealthBookEntries(): Collection
    {
        return $this->healthBookEntries;
    }

    public function addHealthBookEntry(HealthBookEntry $entry): static
    {
        if (!$this->healthBookEntries->contains($entry)) {
            $this->healthBookEntries->add($entry);
            $entry->setAnimal($this);
        }

        return $this;
    }

    public function removeHealthBookEntry(HealthBookEntry $entry): static
    {
        if ($this->healthBookEntries->removeElement($entry)) {
            if ($entry->getAnimal() === $this) {
                $entry->setAnimal(null);
            }
        }

        return $this;
    }

    /** @return Collection<int, AnimalShare> */
    public function getAnimalShares(): Collection
    {
        return $this->animalShares;
    }

    public function addAnimalShare(AnimalShare $share): static
    {
        if (!$this->animalShares->contains($share)) {
            $this->animalShares->add($share);
            $share->setAnimal($this);
        }

        return $this;
    }

    public function removeAnimalShare(AnimalShare $share): static
    {
        if ($this->animalShares->removeElement($share)) {
            if ($share->getAnimal() === $this) {
                $share->setAnimal(null);
            }
        }

        return $this;
    }

    /** @return Collection<int, WeightRecord> */
    public function getWeightRecords(): Collection
    {
        return $this->weightRecords;
    }

    public function addWeightRecord(WeightRecord $record): static
    {
        if (!$this->weightRecords->contains($record)) {
            $this->weightRecords->add($record);
            $record->setAnimal($this);
        }

        return $this;
    }

    public function removeWeightRecord(WeightRecord $record): static
    {
        if ($this->weightRecords->removeElement($record)) {
            if ($record->getAnimal() === $this) {
                $record->setAnimal(null);
            }
        }

        return $this;
    }

    /** @return Collection<int, Allergy> */
    public function getAllergies(): Collection
    {
        return $this->allergies;
    }

    public function addAllergy(Allergy $allergy): static
    {
        if (!$this->allergies->contains($allergy)) {
            $this->allergies->add($allergy);
            $allergy->setAnimal($this);
        }

        return $this;
    }

    public function removeAllergy(Allergy $allergy): static
    {
        if ($this->allergies->removeElement($allergy)) {
            if ($allergy->getAnimal() === $this) {
                $allergy->setAnimal(null);
            }
        }

        return $this;
    }

    public function getActiveTreatments(): array
    {
        return $this->healthBookEntries->filter(fn(HealthBookEntry $e) => $e->isActiveTreatment())->toArray();
    }

    public function getOverdueReminders(): array
    {
        return $this->healthBookEntries->filter(fn(HealthBookEntry $e) => $e->isOverdueReminder())->toArray();
    }
}
