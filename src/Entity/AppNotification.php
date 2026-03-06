<?php

namespace App\Entity;

use App\Repository\AppNotificationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AppNotificationRepository::class)]
#[ORM\Table(name: 'app_notifications')]
class AppNotification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 50)]
    private string $eventType = '';

    #[ORM\Column(length: 255)]
    private string $title = '';

    #[ORM\Column(length: 500)]
    private string $body = '';

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $url = null;

    #[ORM\Column]
    private bool $isRead = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }
    public function getEventType(): string { return $this->eventType; }
    public function setEventType(string $eventType): static { $this->eventType = $eventType; return $this; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }
    public function getBody(): string { return $this->body; }
    public function setBody(string $body): static { $this->body = $body; return $this; }
    public function getUrl(): ?string { return $this->url; }
    public function setUrl(?string $url): static { $this->url = $url; return $this; }
    public function isRead(): bool { return $this->isRead; }
    public function setIsRead(bool $isRead): static { $this->isRead = $isRead; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
