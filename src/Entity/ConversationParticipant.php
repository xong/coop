<?php

namespace App\Entity;

use App\Repository\ConversationParticipantRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConversationParticipantRepository::class)]
#[ORM\Table(name: 'conversation_participants')]
#[ORM\UniqueConstraint(columns: ['conversation_id', 'user_id'])]
class ConversationParticipant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'participants')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Conversation $conversation = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private \DateTimeImmutable $joinedAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastReadAt = null;

    public function __construct()
    {
        $this->joinedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getConversation(): ?Conversation { return $this->conversation; }
    public function setConversation(?Conversation $conversation): static { $this->conversation = $conversation; return $this; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }
    public function getJoinedAt(): \DateTimeImmutable { return $this->joinedAt; }
    public function getLastReadAt(): ?\DateTimeImmutable { return $this->lastReadAt; }
    public function setLastReadAt(?\DateTimeImmutable $lastReadAt): static { $this->lastReadAt = $lastReadAt; return $this; }

    public function markRead(): void
    {
        $this->lastReadAt = new \DateTimeImmutable();
    }
}
