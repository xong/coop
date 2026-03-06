<?php

namespace App\Entity;

use App\Repository\ProjectMemberRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectMemberRepository::class)]
#[ORM\UniqueConstraint(columns: ['project_id', 'user_id'])]
class ProjectMember
{
    const ROLE_ADMIN = 'ADMIN';
    const ROLE_MEMBER = 'MEMBER';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'members')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'projectMemberships')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 20)]
    private string $role = self::ROLE_MEMBER;

    #[ORM\Column]
    private \DateTimeImmutable $joinedAt;

    public function __construct()
    {
        $this->joinedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getProject(): ?Project { return $this->project; }
    public function setProject(?Project $project): static { $this->project = $project; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getRole(): string { return $this->role; }
    public function setRole(string $role): static { $this->role = $role; return $this; }

    public function isAdmin(): bool { return $this->role === self::ROLE_ADMIN; }

    public function getJoinedAt(): \DateTimeImmutable { return $this->joinedAt; }
}
