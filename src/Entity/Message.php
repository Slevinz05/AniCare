<?php

namespace App\Entity;

use App\Repository\MessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
#[ORM\Index(columns: ['animal_id', 'created_at'], name: 'idx_message_animal_date')]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $sender = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Animal $animal = null;

    #[ORM\Column]
    private bool $isRead = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $attachmentFilename = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $attachmentOriginalName = null;

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

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(?User $sender): static
    {
        $this->sender = $sender;

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

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): static
    {
        $this->isRead = $isRead;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getAttachmentFilename(): ?string
    {
        return $this->attachmentFilename;
    }

    public function setAttachmentFilename(?string $attachmentFilename): static
    {
        $this->attachmentFilename = $attachmentFilename;

        return $this;
    }

    public function getAttachmentOriginalName(): ?string
    {
        return $this->attachmentOriginalName;
    }

    public function setAttachmentOriginalName(?string $attachmentOriginalName): static
    {
        $this->attachmentOriginalName = $attachmentOriginalName;

        return $this;
    }
}
