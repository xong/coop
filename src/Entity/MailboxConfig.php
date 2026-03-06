<?php

namespace App\Entity;

use App\Repository\MailboxConfigRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MailboxConfigRepository::class)]
#[ORM\Table(name: 'mailbox_configs')]
class MailboxConfig
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private string $name = '';

    #[ORM\ManyToOne(inversedBy: 'mailboxes')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Organization $organization = null;

    #[ORM\ManyToOne(inversedBy: 'mailboxes')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Project $project = null;

    // IMAP settings
    #[ORM\Column(length: 255)]
    private string $imapHost = '';

    #[ORM\Column]
    private int $imapPort = 993;

    #[ORM\Column(length: 20)]
    private string $imapEncryption = 'ssl'; // ssl, tls, none

    #[ORM\Column(length: 255)]
    private string $imapUsername = '';

    #[ORM\Column(length: 255)]
    private string $imapPassword = '';

    #[ORM\Column(length: 100)]
    private string $imapFolder = 'INBOX';

    // SMTP settings
    #[ORM\Column(length: 255)]
    private string $smtpHost = '';

    #[ORM\Column]
    private int $smtpPort = 587;

    #[ORM\Column(length: 20)]
    private string $smtpEncryption = 'tls'; // ssl, tls, none

    #[ORM\Column(length: 255)]
    private string $smtpUsername = '';

    #[ORM\Column(length: 255)]
    private string $smtpPassword = '';

    #[ORM\Column(length: 255)]
    private string $fromEmail = '';

    #[ORM\Column(length: 150)]
    private string $fromName = '';

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastSyncAt = null;

    #[ORM\Column(nullable: true)]
    private ?string $lastSyncError = null;

    #[ORM\ManyToOne]
    private ?User $createdBy = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(mappedBy: 'mailbox', targetEntity: IncomingEmail::class, orphanRemoval: true)]
    #[ORM\OrderBy(['receivedAt' => 'DESC'])]
    private Collection $emails;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->emails = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getOrganization(): ?Organization { return $this->organization; }
    public function setOrganization(?Organization $organization): static { $this->organization = $organization; return $this; }
    public function getProject(): ?Project { return $this->project; }
    public function setProject(?Project $project): static { $this->project = $project; return $this; }
    public function getImapHost(): string { return $this->imapHost; }
    public function setImapHost(string $imapHost): static { $this->imapHost = $imapHost; return $this; }
    public function getImapPort(): int { return $this->imapPort; }
    public function setImapPort(int $imapPort): static { $this->imapPort = $imapPort; return $this; }
    public function getImapEncryption(): string { return $this->imapEncryption; }
    public function setImapEncryption(string $imapEncryption): static { $this->imapEncryption = $imapEncryption; return $this; }
    public function getImapUsername(): string { return $this->imapUsername; }
    public function setImapUsername(string $imapUsername): static { $this->imapUsername = $imapUsername; return $this; }
    public function getImapPassword(): string { return $this->imapPassword; }
    public function setImapPassword(string $imapPassword): static { $this->imapPassword = $imapPassword; return $this; }
    public function getImapFolder(): string { return $this->imapFolder; }
    public function setImapFolder(string $imapFolder): static { $this->imapFolder = $imapFolder; return $this; }
    public function getSmtpHost(): string { return $this->smtpHost; }
    public function setSmtpHost(string $smtpHost): static { $this->smtpHost = $smtpHost; return $this; }
    public function getSmtpPort(): int { return $this->smtpPort; }
    public function setSmtpPort(int $smtpPort): static { $this->smtpPort = $smtpPort; return $this; }
    public function getSmtpEncryption(): string { return $this->smtpEncryption; }
    public function setSmtpEncryption(string $smtpEncryption): static { $this->smtpEncryption = $smtpEncryption; return $this; }
    public function getSmtpUsername(): string { return $this->smtpUsername; }
    public function setSmtpUsername(string $smtpUsername): static { $this->smtpUsername = $smtpUsername; return $this; }
    public function getSmtpPassword(): string { return $this->smtpPassword; }
    public function setSmtpPassword(string $smtpPassword): static { $this->smtpPassword = $smtpPassword; return $this; }
    public function getFromEmail(): string { return $this->fromEmail; }
    public function setFromEmail(string $fromEmail): static { $this->fromEmail = $fromEmail; return $this; }
    public function getFromName(): string { return $this->fromName; }
    public function setFromName(string $fromName): static { $this->fromName = $fromName; return $this; }
    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): static { $this->isActive = $isActive; return $this; }
    public function getLastSyncAt(): ?\DateTimeImmutable { return $this->lastSyncAt; }
    public function setLastSyncAt(?\DateTimeImmutable $lastSyncAt): static { $this->lastSyncAt = $lastSyncAt; return $this; }
    public function getLastSyncError(): ?string { return $this->lastSyncError; }
    public function setLastSyncError(?string $lastSyncError): static { $this->lastSyncError = $lastSyncError; return $this; }
    public function getCreatedBy(): ?User { return $this->createdBy; }
    public function setCreatedBy(?User $createdBy): static { $this->createdBy = $createdBy; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    /** @return Collection<int, IncomingEmail> */
    public function getEmails(): Collection { return $this->emails; }

    public function getUnreadCount(): int
    {
        return $this->emails->filter(fn(IncomingEmail $e) => !$e->isRead())->count();
    }

    public function getContextLabel(): string
    {
        if ($this->project) {
            return $this->project->getName();
        }
        if ($this->organization) {
            return $this->organization->getName();
        }
        return '';
    }
}
