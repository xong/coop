<?php

namespace App\Entity;

use App\Repository\SharedFileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SharedFileRepository::class)]
class SharedFile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $originalName = null;

    #[ORM\Column(length: 255)]
    private ?string $storagePath = null;

    #[ORM\Column(length: 100)]
    private ?string $mimeType = null;

    #[ORM\Column]
    private int $fileSize = 0;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: FileCategory::class, inversedBy: 'files')]
    #[ORM\JoinColumn(nullable: true)]
    private ?FileCategory $category = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Organization $organization = null;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Project $project = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $uploadedBy = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(targetEntity: FileComment::class, mappedBy: 'file', cascade: ['remove'])]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $comments;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->comments = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getOriginalName(): ?string { return $this->originalName; }
    public function setOriginalName(string $originalName): static { $this->originalName = $originalName; return $this; }

    public function getStoragePath(): ?string { return $this->storagePath; }
    public function setStoragePath(string $storagePath): static { $this->storagePath = $storagePath; return $this; }

    public function getMimeType(): ?string { return $this->mimeType; }
    public function setMimeType(string $mimeType): static { $this->mimeType = $mimeType; return $this; }

    public function getFileSize(): int { return $this->fileSize; }
    public function setFileSize(int $fileSize): static { $this->fileSize = $fileSize; return $this; }

    public function getFileSizeFormatted(): string
    {
        if ($this->fileSize < 1024) return $this->fileSize . ' B';
        if ($this->fileSize < 1048576) return round($this->fileSize / 1024, 1) . ' KB';
        return round($this->fileSize / 1048576, 1) . ' MB';
    }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getCategory(): ?FileCategory { return $this->category; }
    public function setCategory(?FileCategory $category): static { $this->category = $category; return $this; }

    public function getOrganization(): ?Organization { return $this->organization; }
    public function setOrganization(?Organization $organization): static { $this->organization = $organization; return $this; }

    public function getProject(): ?Project { return $this->project; }
    public function setProject(?Project $project): static { $this->project = $project; return $this; }

    public function getUploadedBy(): ?User { return $this->uploadedBy; }
    public function setUploadedBy(?User $uploadedBy): static { $this->uploadedBy = $uploadedBy; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getComments(): Collection { return $this->comments; }

    public function isImage(): bool
    {
        return str_starts_with($this->mimeType ?? '', 'image/');
    }

    public function getIcon(): string
    {
        $mime = $this->mimeType ?? '';
        if (str_starts_with($mime, 'image/')) return '🖼️';
        if (str_contains($mime, 'pdf')) return '📄';
        if (str_contains($mime, 'word') || str_contains($mime, 'document')) return '📝';
        if (str_contains($mime, 'excel') || str_contains($mime, 'spreadsheet')) return '📊';
        if (str_contains($mime, 'zip') || str_contains($mime, 'rar')) return '🗜️';
        if (str_contains($mime, 'video')) return '🎬';
        if (str_contains($mime, 'audio')) return '🎵';
        return '📎';
    }
}
