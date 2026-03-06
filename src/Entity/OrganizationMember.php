<?php

namespace App\Entity;

use App\Repository\OrganizationMemberRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrganizationMemberRepository::class)]
#[ORM\UniqueConstraint(columns: ['organization_id', 'user_id'])]
class OrganizationMember
{
    const ROLE_ADMIN = 'ADMIN';
    const ROLE_MEMBER = 'MEMBER';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Organization::class, inversedBy: 'members')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Organization $organization = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'organizationMemberships')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 20)]
    private string $role = self::ROLE_MEMBER;

    #[ORM\Column]
    private \DateTimeImmutable $joinedAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $invitedBy = null;

    public function __construct()
    {
        $this->joinedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getOrganization(): ?Organization { return $this->organization; }
    public function setOrganization(?Organization $organization): static { $this->organization = $organization; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getRole(): string { return $this->role; }
    public function setRole(string $role): static { $this->role = $role; return $this; }

    public function isAdmin(): bool { return $this->role === self::ROLE_ADMIN; }

    public function getJoinedAt(): \DateTimeImmutable { return $this->joinedAt; }

    public function getInvitedBy(): ?User { return $this->invitedBy; }
    public function setInvitedBy(?User $invitedBy): static { $this->invitedBy = $invitedBy; return $this; }
}
