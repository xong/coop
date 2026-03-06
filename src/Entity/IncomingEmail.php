<?php

namespace App\Entity;

use App\Repository\IncomingEmailRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IncomingEmailRepository::class)]
#[ORM\Table(name: 'incoming_emails')]
class IncomingEmail
{
    const STATUS_NEW        = 'new';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_DONE       = 'done';
    const STATUS_ARCHIVED   = 'archived';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'emails')]
    #[ORM\JoinColumn(nullable: false)]
    private ?MailboxConfig $mailbox = null;

    #[ORM\Column(length: 255, unique: true)]
    private string $messageId = '';

    #[ORM\Column(length: 255)]
    private string $subject = '';

    #[ORM\Column(length: 255)]
    private string $fromEmail = '';

    #[ORM\Column(length: 255)]
    private string $fromName = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $toAddresses = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $ccAddresses = null;

    #[ORM\Column(type: Types::TEXT)]
    private string $bodyText = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $bodyHtml = null;

    #[ORM\Column(type: Types::JSON)]
    private array $attachments = []; // [{name, path, mimeType, size}]

    #[ORM\Column]
    private \DateTimeImmutable $receivedAt;

    #[ORM\Column]
    private bool $isRead = false;

    #[ORM\Column(length: 30)]
    private string $status = self::STATUS_NEW;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $assignedTo = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Topic $linkedTopic = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Project $linkedProject = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $inReplyTo = null;

    #[ORM\OneToMany(mappedBy: 'email', targetEntity: EmailComment::class, orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $comments;

    public function __construct()
    {
        $this->receivedAt = new \DateTimeImmutable();
        $this->comments = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getMailbox(): ?MailboxConfig { return $this->mailbox; }
    public function setMailbox(?MailboxConfig $mailbox): static { $this->mailbox = $mailbox; return $this; }
    public function getMessageId(): string { return $this->messageId; }
    public function setMessageId(string $messageId): static { $this->messageId = $messageId; return $this; }
    public function getSubject(): string { return $this->subject; }
    public function setSubject(string $subject): static { $this->subject = $subject; return $this; }
    public function getFromEmail(): string { return $this->fromEmail; }
    public function setFromEmail(string $fromEmail): static { $this->fromEmail = $fromEmail; return $this; }
    public function getFromName(): string { return $this->fromName; }
    public function setFromName(string $fromName): static { $this->fromName = $fromName; return $this; }
    public function getToAddresses(): ?string { return $this->toAddresses; }
    public function setToAddresses(?string $toAddresses): static { $this->toAddresses = $toAddresses; return $this; }
    public function getCcAddresses(): ?string { return $this->ccAddresses; }
    public function setCcAddresses(?string $ccAddresses): static { $this->ccAddresses = $ccAddresses; return $this; }
    public function getBodyText(): string { return $this->bodyText; }
    public function setBodyText(string $bodyText): static { $this->bodyText = $bodyText; return $this; }
    public function getBodyHtml(): ?string { return $this->bodyHtml; }
    public function setBodyHtml(?string $bodyHtml): static { $this->bodyHtml = $bodyHtml; return $this; }
    public function getAttachments(): array { return $this->attachments; }
    public function setAttachments(array $attachments): static { $this->attachments = $attachments; return $this; }
    public function getReceivedAt(): \DateTimeImmutable { return $this->receivedAt; }
    public function setReceivedAt(\DateTimeImmutable $receivedAt): static { $this->receivedAt = $receivedAt; return $this; }
    public function isRead(): bool { return $this->isRead; }
    public function setIsRead(bool $isRead): static { $this->isRead = $isRead; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }
    public function getAssignedTo(): ?User { return $this->assignedTo; }
    public function setAssignedTo(?User $assignedTo): static { $this->assignedTo = $assignedTo; return $this; }
    public function getLinkedTopic(): ?Topic { return $this->linkedTopic; }
    public function setLinkedTopic(?Topic $linkedTopic): static { $this->linkedTopic = $linkedTopic; return $this; }
    public function getLinkedProject(): ?Project { return $this->linkedProject; }
    public function setLinkedProject(?Project $linkedProject): static { $this->linkedProject = $linkedProject; return $this; }
    public function getInReplyTo(): ?string { return $this->inReplyTo; }
    public function setInReplyTo(?string $inReplyTo): static { $this->inReplyTo = $inReplyTo; return $this; }

    /** @return Collection<int, EmailComment> */
    public function getComments(): Collection { return $this->comments; }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_NEW         => 'Neu',
            self::STATUS_IN_PROGRESS => 'In Bearbeitung',
            self::STATUS_DONE        => 'Erledigt',
            self::STATUS_ARCHIVED    => 'Archiviert',
            default                  => $this->status,
        };
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            self::STATUS_NEW         => 'blue',
            self::STATUS_IN_PROGRESS => 'amber',
            self::STATUS_DONE        => 'green',
            self::STATUS_ARCHIVED    => 'gray',
            default                  => 'gray',
        };
    }

    public function getSenderInitials(): string
    {
        if ($this->fromName) {
            $parts = explode(' ', trim($this->fromName));
            $initials = strtoupper($parts[0][0] ?? '');
            if (isset($parts[1])) {
                $initials .= strtoupper($parts[1][0]);
            }
            return $initials;
        }
        return strtoupper($this->fromEmail[0] ?? '?');
    }
}
