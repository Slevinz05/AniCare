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

    /**
     * @var Collection<int, HealthBookEntry>
     */
    #[ORM\OneToMany(targetEntity: HealthBookEntry::class, mappedBy: 'animal')]
    private Collection $healthBookEntries;

    /**
     * @var Collection<int, AnimalShare>
     */
    #[ORM\OneToMany(targetEntity: AnimalShare::class, mappedBy: 'animal')]
    private Collection $animalShares;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Assert\All([
        new Assert\File(
            maxSize: '10M',
            mimeTypes: [
                'application/pdf',
                'image/jpeg',
                'image/png',
                'image/webp',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
            maxSizeMessage: 'Le fichier est trop volumineux ({{ size }} {{ suffix }}). La taille maximale autorisée est {{ limit }} {{ suffix }}.',
            mimeTypesMessage: 'Veuillez ajouter un fichier valide : PDF, image ou document Word.',
        )
    ])]
    private ?array $documents = [];

    #[ORM\ManyToOne(inversedBy: 'animals')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    public function __construct()
    {
        $this->healthBookEntries = new ArrayCollection();
        $this->animalShares = new ArrayCollection();
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

    /**
     * @return Collection<int, HealthBookEntry>
     */
    public function getHealthBookEntries(): Collection
    {
        return $this->healthBookEntries;
    }

    public function addHealthBookEntry(HealthBookEntry $healthBookEntry): static
    {
        if (!$this->healthBookEntries->contains($healthBookEntry)) {
            $this->healthBookEntries->add($healthBookEntry);
            $healthBookEntry->setAnimal($this);
        }

        return $this;
    }

    public function removeHealthBookEntry(HealthBookEntry $healthBookEntry): static
    {
        if ($this->healthBookEntries->removeElement($healthBookEntry)) {
            if ($healthBookEntry->getAnimal() === $this) {
                $healthBookEntry->setAnimal(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AnimalShare>
     */
    public function getAnimalShares(): Collection
    {
        return $this->animalShares;
    }

    public function addAnimalShare(AnimalShare $animalShare): static
    {
        if (!$this->animalShares->contains($animalShare)) {
            $this->animalShares->add($animalShare);
            $animalShare->setAnimal($this);
        }

        return $this;
    }

    public function removeAnimalShare(AnimalShare $animalShare): static
    {
        if ($this->animalShares->removeElement($animalShare)) {
            if ($animalShare->getAnimal() === $this) {
                $animalShare->setAnimal(null);
            }
        }

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
}