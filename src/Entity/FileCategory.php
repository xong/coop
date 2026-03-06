<?php

namespace App\Entity;

use App\Repository\FileCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FileCategoryRepository::class)]
class FileCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Organization $organization = null;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Project $project = null;

    #[ORM\ManyToOne(targetEntity: FileCategory::class, inversedBy: 'children')]
    #[ORM\JoinColumn(nullable: true)]
    private ?FileCategory $parent = null;

    #[ORM\OneToMany(targetEntity: FileCategory::class, mappedBy: 'parent', cascade: ['remove'])]
    private Collection $children;

    #[ORM\OneToMany(targetEntity: SharedFile::class, mappedBy: 'category', cascade: ['remove'])]
    private Collection $files;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->children = new ArrayCollection();
        $this->files = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getOrganization(): ?Organization { return $this->organization; }
    public function setOrganization(?Organization $organization): static { $this->organization = $organization; return $this; }

    public function getProject(): ?Project { return $this->project; }
    public function setProject(?Project $project): static { $this->project = $project; return $this; }

    public function getParent(): ?FileCategory { return $this->parent; }
    public function setParent(?FileCategory $parent): static { $this->parent = $parent; return $this; }

    public function getChildren(): Collection { return $this->children; }
    public function getFiles(): Collection { return $this->files; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getCreatedBy(): ?User { return $this->createdBy; }
    public function setCreatedBy(?User $createdBy): static { $this->createdBy = $createdBy; return $this; }
}
