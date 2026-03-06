<?php

namespace App\Entity;

use App\Repository\DirectMessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DirectMessageRepository::class)]
#[ORM\Table(name: 'direct_messages')]
class DirectMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Conversation $conversation = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $sender = null;

    #[ORM\Column(type: Types::TEXT)]
    private string $content = '';

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private bool $isDeleted = false;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getConversation(): ?Conversation { return $this->conversation; }
    public function setConversation(?Conversation $conversation): static { $this->conversation = $conversation; return $this; }
    public function getSender(): ?User { return $this->sender; }
    public function setSender(?User $sender): static { $this->sender = $sender; return $this; }
    public function getContent(): string { return $this->content; }
    public function setContent(string $content): static { $this->content = $content; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function isDeleted(): bool { return $this->isDeleted; }
    public function setIsDeleted(bool $isDeleted): static { $this->isDeleted = $isDeleted; return $this; }
}
