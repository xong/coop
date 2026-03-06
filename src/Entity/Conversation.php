<?php

namespace App\Entity;

use App\Repository\ConversationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConversationRepository::class)]
#[ORM\Table(name: 'conversations')]
class Conversation
{
    const TYPE_DIRECT = 'direct';
    const TYPE_GROUP  = 'group';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private string $type = self::TYPE_DIRECT;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $title = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Organization $organization = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastMessageAt = null;

    #[ORM\OneToMany(mappedBy: 'conversation', targetEntity: ConversationParticipant::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $participants;

    #[ORM\OneToMany(mappedBy: 'conversation', targetEntity: DirectMessage::class, cascade: ['remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $messages;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->participants = new ArrayCollection();
        $this->messages = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getType(): string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }
    public function getTitle(): ?string { return $this->title; }
    public function setTitle(?string $title): static { $this->title = $title; return $this; }
    public function getOrganization(): ?Organization { return $this->organization; }
    public function setOrganization(?Organization $organization): static { $this->organization = $organization; return $this; }
    public function getCreatedBy(): ?User { return $this->createdBy; }
    public function setCreatedBy(?User $createdBy): static { $this->createdBy = $createdBy; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getLastMessageAt(): ?\DateTimeImmutable { return $this->lastMessageAt; }
    public function setLastMessageAt(?\DateTimeImmutable $lastMessageAt): static { $this->lastMessageAt = $lastMessageAt; return $this; }

    /** @return Collection<int, ConversationParticipant> */
    public function getParticipants(): Collection { return $this->participants; }

    /** @return Collection<int, DirectMessage> */
    public function getMessages(): Collection { return $this->messages; }

    public function isDirect(): bool { return $this->type === self::TYPE_DIRECT; }
    public function isGroup(): bool  { return $this->type === self::TYPE_GROUP; }

    public function hasParticipant(User $user): bool
    {
        foreach ($this->participants as $p) {
            if ($p->getUser() === $user) return true;
        }
        return false;
    }

    public function getParticipantFor(User $user): ?ConversationParticipant
    {
        foreach ($this->participants as $p) {
            if ($p->getUser() === $user) return $p;
        }
        return null;
    }

    /** For direct conversations: return the other user */
    public function getOtherParticipant(User $me): ?User
    {
        foreach ($this->participants as $p) {
            if ($p->getUser() !== $me) return $p->getUser();
        }
        return null;
    }

    public function getDisplayTitle(User $me): string
    {
        if ($this->title) return $this->title;
        if ($this->isDirect()) {
            $other = $this->getOtherParticipant($me);
            return $other ? $other->getFullName() : 'Direktnachricht';
        }
        $names = [];
        foreach ($this->participants as $p) {
            if ($p->getUser() !== $me) {
                $names[] = $p->getUser()->getFirstName();
            }
        }
        return implode(', ', $names) ?: 'Gruppenunterhaltung';
    }

    public function getLastMessage(): ?DirectMessage
    {
        if ($this->messages->isEmpty()) return null;
        $msgs = $this->messages->toArray();
        return end($msgs) ?: null;
    }

    public function getUnreadCount(User $user): int
    {
        $participant = $this->getParticipantFor($user);
        if (!$participant) return 0;
        $lastRead = $participant->getLastReadAt();

        $count = 0;
        foreach ($this->messages as $msg) {
            if ($msg->getSender() === $user) continue;
            if ($msg->isDeleted()) continue;
            if ($lastRead === null || $msg->getCreatedAt() > $lastRead) {
                $count++;
            }
        }
        return $count;
    }
}
