<?php

namespace App\Entity;

use App\Repository\TopicRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TopicRepository::class)]
class Topic
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Organization $organization = null;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Project $project = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastActivityAt = null;

    #[ORM\Column]
    private bool $isPinned = false;

    #[ORM\Column]
    private bool $isClosed = false;

    #[ORM\OneToMany(targetEntity: TopicPost::class, mappedBy: 'topic', cascade: ['remove'])]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $posts;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->lastActivityAt = new \DateTimeImmutable();
        $this->posts = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getTitle(): ?string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }

    public function getOrganization(): ?Organization { return $this->organization; }
    public function setOrganization(?Organization $organization): static { $this->organization = $organization; return $this; }

    public function getProject(): ?Project { return $this->project; }
    public function setProject(?Project $project): static { $this->project = $project; return $this; }

    public function getCreatedBy(): ?User { return $this->createdBy; }
    public function setCreatedBy(?User $createdBy): static { $this->createdBy = $createdBy; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getLastActivityAt(): ?\DateTimeImmutable { return $this->lastActivityAt; }
    public function setLastActivityAt(?\DateTimeImmutable $lastActivityAt): static { $this->lastActivityAt = $lastActivityAt; return $this; }

    public function isPinned(): bool { return $this->isPinned; }
    public function setIsPinned(bool $isPinned): static { $this->isPinned = $isPinned; return $this; }

    public function isClosed(): bool { return $this->isClosed; }
    public function setIsClosed(bool $isClosed): static { $this->isClosed = $isClosed; return $this; }

    public function getPosts(): Collection { return $this->posts; }

    public function getPostCount(): int { return $this->posts->count(); }

    public function getLastPost(): ?TopicPost
    {
        $posts = $this->posts->toArray();
        return $posts ? end($posts) : null;
    }
}
