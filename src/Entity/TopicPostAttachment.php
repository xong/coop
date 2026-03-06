<?php

namespace App\Entity;

use App\Repository\TopicPostAttachmentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TopicPostAttachmentRepository::class)]
class TopicPostAttachment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TopicPost::class, inversedBy: 'attachments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TopicPost $post = null;

    #[ORM\Column(length: 255)]
    private ?string $originalName = null;

    #[ORM\Column(length: 255)]
    private ?string $storagePath = null;

    #[ORM\Column(length: 100)]
    private ?string $mimeType = null;

    #[ORM\Column]
    private int $fileSize = 0;

    public function getId(): ?int { return $this->id; }

    public function getPost(): ?TopicPost { return $this->post; }
    public function setPost(?TopicPost $post): static { $this->post = $post; return $this; }

    public function getOriginalName(): ?string { return $this->originalName; }
    public function setOriginalName(string $originalName): static { $this->originalName = $originalName; return $this; }

    public function getStoragePath(): ?string { return $this->storagePath; }
    public function setStoragePath(string $storagePath): static { $this->storagePath = $storagePath; return $this; }

    public function getMimeType(): ?string { return $this->mimeType; }
    public function setMimeType(string $mimeType): static { $this->mimeType = $mimeType; return $this; }

    public function getFileSize(): int { return $this->fileSize; }
    public function setFileSize(int $fileSize): static { $this->fileSize = $fileSize; return $this; }

    public function isImage(): bool { return str_starts_with($this->mimeType ?? '', 'image/'); }

    public function getFileSizeFormatted(): string
    {
        if ($this->fileSize < 1024) return $this->fileSize . ' B';
        if ($this->fileSize < 1048576) return round($this->fileSize / 1024, 1) . ' KB';
        return round($this->fileSize / 1048576, 1) . ' MB';
    }
}
