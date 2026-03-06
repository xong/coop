<?php

namespace App\Entity;

use App\Repository\ProjectInvitationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectInvitationRepository::class)]
class ProjectInvitation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'invitations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $token = null;

    #[ORM\Column(length: 20)]
    private string $role = ProjectMember::ROLE_MEMBER;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $acceptedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $invitedBy = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->expiresAt = new \DateTimeImmutable('+7 days');
        $this->token = bin2hex(random_bytes(32));
    }

    public function getId(): ?int { return $this->id; }

    public function getProject(): ?Project { return $this->project; }
    public function setProject(?Project $project): static { $this->project = $project; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getToken(): ?string { return $this->token; }

    public function getRole(): string { return $this->role; }
    public function setRole(string $role): static { $this->role = $role; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getExpiresAt(): \DateTimeImmutable { return $this->expiresAt; }

    public function getAcceptedAt(): ?\DateTimeImmutable { return $this->acceptedAt; }
    public function setAcceptedAt(?\DateTimeImmutable $acceptedAt): static { $this->acceptedAt = $acceptedAt; return $this; }

    public function getInvitedBy(): ?User { return $this->invitedBy; }
    public function setInvitedBy(?User $invitedBy): static { $this->invitedBy = $invitedBy; return $this; }

    public function isExpired(): bool { return $this->expiresAt < new \DateTimeImmutable(); }
    public function isAccepted(): bool { return $this->acceptedAt !== null; }
}
