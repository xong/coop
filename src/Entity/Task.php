<?php

namespace App\Entity;

use App\Repository\TaskRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
class Task
{
    const STATUS_OPEN       = 'open';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_DONE       = 'done';
    const STATUS_CANCELLED  = 'cancelled';

    const PRIORITY_LOW    = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH   = 'high';
    const PRIORITY_URGENT = 'urgent';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_OPEN;

    #[ORM\Column(length: 20)]
    private string $priority = self::PRIORITY_MEDIUM;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dueDate = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Organization $organization = null;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Project $project = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'task_assignees')]
    private Collection $assignees;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->assignees = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getTitle(): ?string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getPriority(): string { return $this->priority; }
    public function setPriority(string $priority): static { $this->priority = $priority; return $this; }

    public function getDueDate(): ?\DateTimeImmutable { return $this->dueDate; }
    public function setDueDate(?\DateTimeImmutable $dueDate): static { $this->dueDate = $dueDate; return $this; }

    public function getOrganization(): ?Organization { return $this->organization; }
    public function setOrganization(?Organization $organization): static { $this->organization = $organization; return $this; }

    public function getProject(): ?Project { return $this->project; }
    public function setProject(?Project $project): static { $this->project = $project; return $this; }

    public function getCreatedBy(): ?User { return $this->createdBy; }
    public function setCreatedBy(?User $createdBy): static { $this->createdBy = $createdBy; return $this; }

    public function getAssignees(): Collection { return $this->assignees; }
    public function addAssignee(User $user): static { if (!$this->assignees->contains($user)) $this->assignees->add($user); return $this; }
    public function removeAssignee(User $user): static { $this->assignees->removeElement($user); return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getCompletedAt(): ?\DateTimeImmutable { return $this->completedAt; }
    public function setCompletedAt(?\DateTimeImmutable $completedAt): static { $this->completedAt = $completedAt; return $this; }

    public function isDone(): bool { return $this->status === self::STATUS_DONE; }
    public function isOverdue(): bool
    {
        return $this->dueDate !== null && $this->dueDate < new \DateTimeImmutable() && !$this->isDone();
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN => 'Offen',
            self::STATUS_IN_PROGRESS => 'In Bearbeitung',
            self::STATUS_DONE => 'Erledigt',
            self::STATUS_CANCELLED => 'Abgebrochen',
            default => $this->status,
        };
    }

    public function getPriorityLabel(): string
    {
        return match ($this->priority) {
            self::PRIORITY_LOW => 'Niedrig',
            self::PRIORITY_MEDIUM => 'Mittel',
            self::PRIORITY_HIGH => 'Hoch',
            self::PRIORITY_URGENT => 'Dringend',
            default => $this->priority,
        };
    }

    public function getPriorityColor(): string
    {
        return match ($this->priority) {
            self::PRIORITY_LOW => 'badge-gray',
            self::PRIORITY_MEDIUM => 'badge-blue',
            self::PRIORITY_HIGH => 'badge-red',
            self::PRIORITY_URGENT => 'badge-red',
            default => 'badge-gray',
        };
    }
}
