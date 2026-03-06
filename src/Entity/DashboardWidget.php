<?php

namespace App\Entity;

use App\Repository\DashboardWidgetRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DashboardWidgetRepository::class)]
#[ORM\Table(name: 'dashboard_widgets')]
#[ORM\UniqueConstraint(columns: ['user_id', 'widget_type'])]
class DashboardWidget
{
    const WIDGET_ORGANIZATIONS    = 'organizations';
    const WIDGET_MY_TASKS         = 'my_tasks';
    const WIDGET_UPCOMING_EVENTS  = 'upcoming_events';
    const WIDGET_RECENT_TOPICS    = 'recent_topics';
    const WIDGET_RECENT_FILES     = 'recent_files';
    const WIDGET_MESSAGES         = 'messages';
    const WIDGET_RECENT_EMAILS    = 'recent_emails';

    const ALL_TYPES = [
        self::WIDGET_RECENT_EMAILS,
        self::WIDGET_MESSAGES,
        self::WIDGET_UPCOMING_EVENTS,
        self::WIDGET_MY_TASKS,
        self::WIDGET_RECENT_TOPICS,
        self::WIDGET_RECENT_FILES,
        self::WIDGET_ORGANIZATIONS,
    ];

    const LABELS = [
        self::WIDGET_ORGANIZATIONS   => 'Meine Organisationen',
        self::WIDGET_MY_TASKS        => 'Meine Aufgaben',
        self::WIDGET_UPCOMING_EVENTS => 'Anstehende Termine',
        self::WIDGET_RECENT_TOPICS   => 'Aktuelle Themen',
        self::WIDGET_RECENT_FILES    => 'Neueste Dateien',
        self::WIDGET_MESSAGES        => 'Nachrichten',
        self::WIDGET_RECENT_EMAILS   => 'Neueste E-Mails',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 50)]
    private string $widgetType = '';

    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column]
    private bool $isEnabled = true;

    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }
    public function getWidgetType(): string { return $this->widgetType; }
    public function setWidgetType(string $widgetType): static { $this->widgetType = $widgetType; return $this; }
    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): static { $this->position = $position; return $this; }
    public function isEnabled(): bool { return $this->isEnabled; }
    public function setIsEnabled(bool $isEnabled): static { $this->isEnabled = $isEnabled; return $this; }

    public function getLabel(): string
    {
        return self::LABELS[$this->widgetType] ?? $this->widgetType;
    }
}
