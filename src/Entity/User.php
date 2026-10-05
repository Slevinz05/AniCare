<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'Un compte existe déjà avec cette adresse e-mail.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    /**
     * @var Collection<int, Animal>
     */
    #[ORM\OneToMany(targetEntity: Animal::class, mappedBy: 'owner', orphanRemoval: true)]
    private Collection $animals;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $postalCode = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $bio = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $acceptsNotifications = true;

    #[ORM\Column(length: 20)]
    private ?string $accountType = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $specialty = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $profilePhotos = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $profileDescription = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $website = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $interventionDepartments = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $experienceYears = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $defaultConsultationDuration = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $defaultPublicNotes = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $defaultAppointmentNotes = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $openingHours = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $directoryVisible = false;

    #[ORM\Column(options: ['default' => true])]
    private bool $openingHoursVisible = true;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $rehabilitationTemplates = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeCustomerId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeSubscriptionId = null;

    #[ORM\Column(length: 20, options: ['default' => 'inactive'])]
    private string $subscriptionStatus = 'inactive';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $subscriptionEndsAt = null;

    /** @var Collection<int, StructureMembership> */
    #[ORM\OneToMany(targetEntity: StructureMembership::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $structureMemberships;

    /** @var Collection<int, AnimalReferent> */
    #[ORM\OneToMany(targetEntity: AnimalReferent::class, mappedBy: 'user')]
    private Collection $animalReferents;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $activeSpace = null;

    public function __construct()
    {
        $this->animals = new ArrayCollection();
        $this->structureMemberships = new ArrayCollection();
        $this->animalReferents = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
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
            $animal->setOwner($this);
        }

        return $this;
    }

    public function removeAnimal(Animal $animal): static
    {
        if ($this->animals->removeElement($animal)) {
            // set the owning side to null (unless already changed)
            if ($animal->getOwner() === $this) {
                $animal->setOwner(null);
            }
        }

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFullName(): string
    {
        if ($this->firstName && $this->lastName) {
            return $this->firstName . ' ' . $this->lastName;
        }

        return $this->firstName ?? $this->lastName ?? $this->email;
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

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): static
    {
        $this->bio = $bio;

        return $this;
    }

    public function isAcceptsNotifications(): bool
    {
        return $this->acceptsNotifications;
    }

    public function setAcceptsNotifications(bool $acceptsNotifications): static
    {
        $this->acceptsNotifications = $acceptsNotifications;

        return $this;
    }

    public function getAccountType(): ?string
    {
        return $this->accountType;
    }

    public function setAccountType(string $accountType): static
    {
        $this->accountType = $accountType;

        return $this;
    }

    public function getStripeCustomerId(): ?string
    {
        return $this->stripeCustomerId;
    }

    public function setStripeCustomerId(?string $stripeCustomerId): static
    {
        $this->stripeCustomerId = $stripeCustomerId;

        return $this;
    }

    public function getStripeSubscriptionId(): ?string
    {
        return $this->stripeSubscriptionId;
    }

    public function setStripeSubscriptionId(?string $stripeSubscriptionId): static
    {
        $this->stripeSubscriptionId = $stripeSubscriptionId;

        return $this;
    }

    public function getSubscriptionStatus(): string
    {
        return $this->subscriptionStatus;
    }

    public function setSubscriptionStatus(string $subscriptionStatus): static
    {
        $this->subscriptionStatus = $subscriptionStatus;

        return $this;
    }

    public function getSubscriptionEndsAt(): ?\DateTimeImmutable
    {
        return $this->subscriptionEndsAt;
    }

    public function setSubscriptionEndsAt(?\DateTimeImmutable $subscriptionEndsAt): static
    {
        $this->subscriptionEndsAt = $subscriptionEndsAt;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

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

    public function getSpecialty(): ?string
    {
        return $this->specialty;
    }

    public function setSpecialty(?string $specialty): static
    {
        $this->specialty = $specialty;

        return $this;
    }

    public function getDepartment(): ?string
    {
        if ($this->postalCode && strlen($this->postalCode) >= 2) {
            return substr($this->postalCode, 0, 2);
        }

        return null;
    }

    public function getFullAddress(): ?string
    {
        $parts = array_filter([$this->address, $this->postalCode, $this->city]);

        return $parts ? implode(', ', $parts) : null;
    }

    public function getProfilePhotos(): ?array
    {
        return $this->profilePhotos;
    }

    public function setProfilePhotos(?array $profilePhotos): static
    {
        $this->profilePhotos = $profilePhotos;

        return $this;
    }

    public function addProfilePhoto(array $photo): static
    {
        $photos = $this->profilePhotos ?? [];
        $photos[] = $photo;
        $this->profilePhotos = $photos;

        return $this;
    }

    public function removeProfilePhoto(string $fileName): static
    {
        $this->profilePhotos = array_values(array_filter(
            $this->profilePhotos ?? [],
            fn (array $p) => ($p['fileName'] ?? '') !== $fileName,
        ));

        return $this;
    }

    public function getProfileDescription(): ?string
    {
        return $this->profileDescription;
    }

    public function setProfileDescription(?string $profileDescription): static
    {
        $this->profileDescription = $profileDescription;

        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): static
    {
        $this->website = $website;

        return $this;
    }

    public function getInterventionDepartments(): ?array
    {
        return $this->interventionDepartments;
    }

    public function setInterventionDepartments(?array $interventionDepartments): static
    {
        $this->interventionDepartments = $interventionDepartments;

        return $this;
    }

    public function getInterventionDepartmentsLabel(): ?string
    {
        if (empty($this->interventionDepartments)) {
            return null;
        }

        return implode(', ', $this->interventionDepartments);
    }

    public function getExperienceYears(): ?int
    {
        return $this->experienceYears;
    }

    public function setExperienceYears(?int $experienceYears): static
    {
        $this->experienceYears = $experienceYears;

        return $this;
    }

    public function getDefaultConsultationDuration(): ?int
    {
        return $this->defaultConsultationDuration;
    }

    public function setDefaultConsultationDuration(?int $defaultConsultationDuration): static
    {
        $this->defaultConsultationDuration = $defaultConsultationDuration;
        return $this;
    }

    public function getDefaultPublicNotes(): ?string
    {
        return $this->defaultPublicNotes;
    }

    public function setDefaultPublicNotes(?string $defaultPublicNotes): static
    {
        $this->defaultPublicNotes = $defaultPublicNotes;
        return $this;
    }

    public function getDefaultAppointmentNotes(): ?string
    {
        return $this->defaultAppointmentNotes;
    }

    public function setDefaultAppointmentNotes(?string $defaultAppointmentNotes): static
    {
        $this->defaultAppointmentNotes = $defaultAppointmentNotes;
        return $this;
    }

    public function getOpeningHours(): ?array
    {
        return $this->openingHours;
    }

    public function setOpeningHours(?array $openingHours): static
    {
        $this->openingHours = $openingHours;

        return $this;
    }

    public function isDirectoryVisible(): bool
    {
        return $this->directoryVisible;
    }

    public function setDirectoryVisible(bool $directoryVisible): static
    {
        $this->directoryVisible = $directoryVisible;

        return $this;
    }

    public function isOpeningHoursVisible(): bool
    {
        return $this->openingHoursVisible;
    }

    public function setOpeningHoursVisible(bool $openingHoursVisible): static
    {
        $this->openingHoursVisible = $openingHoursVisible;

        return $this;
    }

    public function getRehabilitationTemplates(): array
    {
        return $this->rehabilitationTemplates ?? [];
    }

    public function setRehabilitationTemplates(?array $rehabilitationTemplates): static
    {
        $this->rehabilitationTemplates = $rehabilitationTemplates;

        return $this;
    }

    public function hasActiveSubscription(): bool
    {
        if ($this->subscriptionStatus === 'active') {
            return true;
        }

        if ($this->subscriptionStatus === 'canceled' && $this->subscriptionEndsAt && $this->subscriptionEndsAt > new \DateTimeImmutable()) {
            return true;
        }

        return false;
    }

    /** @return Collection<int, StructureMembership> */
    public function getStructureMemberships(): Collection
    {
        return $this->structureMemberships;
    }

    public function addStructureMembership(StructureMembership $membership): static
    {
        if (!$this->structureMemberships->contains($membership)) {
            $this->structureMemberships->add($membership);
            $membership->setUser($this);
        }

        return $this;
    }

    public function removeStructureMembership(StructureMembership $membership): static
    {
        if ($this->structureMemberships->removeElement($membership)) {
            if ($membership->getUser() === $this) {
                $membership->setUser(null);
            }
        }

        return $this;
    }

    public function getStructures(): array
    {
        return $this->structureMemberships->map(
            fn (StructureMembership $m) => $m->getStructure()
        )->toArray();
    }

    /** @return Collection<int, AnimalReferent> */
    public function getAnimalReferents(): Collection
    {
        return $this->animalReferents;
    }

    public function getActiveSpace(): ?string
    {
        return $this->activeSpace;
    }

    public function setActiveSpace(?string $activeSpace): static
    {
        $this->activeSpace = $activeSpace;
        return $this;
    }

    public function hasProSpace(): bool
    {
        return in_array('ROLE_PRO', $this->getRoles(), true);
    }

    public function hasParticulierSpace(): bool
    {
        return in_array($this->accountType, ['OWNER', 'BOTH'], true)
            || (!$this->hasProSpace() && $this->accountType !== 'STRUCTURE');
    }

    public function hasBothSpaces(): bool
    {
        return $this->hasProSpace() && $this->hasParticulierSpace();
    }

    public function isInProSpace(): bool
    {
        if ($this->activeSpace === 'professionnel') {
            return true;
        }
        if ($this->activeSpace === 'particulier') {
            return false;
        }
        return $this->hasProSpace();
    }

    public function enableProSpace(): static
    {
        if (!in_array('ROLE_PRO', $this->roles, true)) {
            $this->roles[] = 'ROLE_PRO';
        }
        if ($this->accountType === 'OWNER') {
            $this->accountType = 'BOTH';
        }
        return $this;
    }
}
