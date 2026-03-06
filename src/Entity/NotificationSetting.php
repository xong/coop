<?php

namespace App\Entity;

use App\Repository\NotificationSettingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationSettingRepository::class)]
#[ORM\Table(name: 'notification_settings')]
#[ORM\UniqueConstraint(columns: ['user_id', 'event_type'])]
class NotificationSetting
{
    const EVENT_TASK_ASSIGNED        = 'task_assigned';
    const EVENT_TOPIC_REPLY          = 'topic_reply';
    const EVENT_FILE_UPLOADED        = 'file_uploaded';
    const EVENT_CALENDAR_EVENT       = 'calendar_event';
    const EVENT_MESSAGE_RECEIVED     = 'message_received';
    const EVENT_ORG_INVITATION       = 'org_invitation';
    const EVENT_PROJECT_INVITATION   = 'project_invitation';
    const EVENT_EMAIL_RECEIVED       = 'email_received';

    const FREQ_IMMEDIATE = 'immediate';
    const FREQ_DIGEST    = 'digest';

    const EVENT_LABELS = [
        self::EVENT_TASK_ASSIGNED      => 'Aufgabe zugewiesen',
        self::EVENT_TOPIC_REPLY        => 'Antwort im Forum',
        self::EVENT_FILE_UPLOADED      => 'Neue Datei hochgeladen',
        self::EVENT_CALENDAR_EVENT     => 'Neuer Termin erstellt',
        self::EVENT_MESSAGE_RECEIVED   => 'Neue Nachricht',
        self::EVENT_ORG_INVITATION     => 'Einladung zur Organisation',
        self::EVENT_PROJECT_INVITATION => 'Einladung zum Projekt',
        self::EVENT_EMAIL_RECEIVED     => 'Neue eingehende E-Mail',
    ];

    /** Default settings: [inApp, email, frequency] */
    const DEFAULTS = [
        self::EVENT_TASK_ASSIGNED      => [true, true,  self::FREQ_IMMEDIATE],
        self::EVENT_TOPIC_REPLY        => [true, true,  self::FREQ_IMMEDIATE],
        self::EVENT_FILE_UPLOADED      => [true, false, self::FREQ_DIGEST],
        self::EVENT_CALENDAR_EVENT     => [true, false, self::FREQ_IMMEDIATE],
        self::EVENT_MESSAGE_RECEIVED   => [true, false, self::FREQ_IMMEDIATE],
        self::EVENT_ORG_INVITATION     => [true, true,  self::FREQ_IMMEDIATE],
        self::EVENT_PROJECT_INVITATION => [true, true,  self::FREQ_IMMEDIATE],
        self::EVENT_EMAIL_RECEIVED     => [true, true,  self::FREQ_IMMEDIATE],
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 50)]
    private string $eventType = '';

    #[ORM\Column]
    private bool $inApp = true;

    #[ORM\Column]
    private bool $email = false;

    #[ORM\Column(length: 20)]
    private string $emailFrequency = self::FREQ_IMMEDIATE;

    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }
    public function getEventType(): string { return $this->eventType; }
    public function setEventType(string $eventType): static { $this->eventType = $eventType; return $this; }
    public function isInApp(): bool { return $this->inApp; }
    public function setInApp(bool $inApp): static { $this->inApp = $inApp; return $this; }
    public function isEmail(): bool { return $this->email; }
    public function setEmail(bool $email): static { $this->email = $email; return $this; }
    public function getEmailFrequency(): string { return $this->emailFrequency; }
    public function setEmailFrequency(string $emailFrequency): static { $this->emailFrequency = $emailFrequency; return $this; }

    public function getLabel(): string
    {
        return self::EVENT_LABELS[$this->eventType] ?? $this->eventType;
    }
}
