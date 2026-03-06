<?php

namespace App\Entity;

use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\UniqueConstraint(columns: ['organization_id', 'slug'])]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Organization::class, inversedBy: 'projects')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Organization $organization = null;

    #[ORM\Column(length: 150)]
    private ?string $name = null;

    #[ORM\Column(length: 150)]
    private ?string $slug = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private bool $isPublic = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[ORM\OneToMany(targetEntity: ProjectMember::class, mappedBy: 'project', cascade: ['persist', 'remove'])]
    private Collection $members;

    #[ORM\OneToMany(targetEntity: ProjectInvitation::class, mappedBy: 'project', cascade: ['remove'])]
    private Collection $invitations;

    #[ORM\OneToMany(mappedBy: 'project', targetEntity: MailboxConfig::class, cascade: ['remove'])]
    private Collection $mailboxes;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->members = new ArrayCollection();
        $this->invitations = new ArrayCollection();
        $this->mailboxes = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getOrganization(): ?Organization { return $this->organization; }
    public function setOrganization(?Organization $organization): static { $this->organization = $organization; return $this; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getSlug(): ?string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function isPublic(): bool { return $this->isPublic; }
    public function setIsPublic(bool $isPublic): static { $this->isPublic = $isPublic; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getCreatedBy(): ?User { return $this->createdBy; }
    public function setCreatedBy(?User $createdBy): static { $this->createdBy = $createdBy; return $this; }

    public function getMembers(): Collection { return $this->members; }
    public function getInvitations(): Collection { return $this->invitations; }
    public function getMailboxes(): Collection { return $this->mailboxes; }

    public function isMember(User $user): bool
    {
        foreach ($this->members as $member) {
            if ($member->getUser() === $user) return true;
        }
        return false;
    }

    public function isAdmin(User $user): bool
    {
        foreach ($this->members as $member) {
            if ($member->getUser() === $user && $member->getRole() === ProjectMember::ROLE_ADMIN) return true;
        }
        return false;
    }
}
