<?php

namespace App\Entity;

use App\Repository\OrganizationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: OrganizationRepository::class)]
#[UniqueEntity(fields: ['slug'], message: 'Dieser Name ist bereits vergeben.')]
class Organization
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private ?string $name = null;

    #[ORM\Column(length: 150, unique: true)]
    private ?string $slug = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    #[ORM\OneToMany(targetEntity: OrganizationMember::class, mappedBy: 'organization', cascade: ['persist', 'remove'])]
    private Collection $members;

    #[ORM\OneToMany(targetEntity: OrganizationInvitation::class, mappedBy: 'organization', cascade: ['remove'])]
    private Collection $invitations;

    #[ORM\OneToMany(targetEntity: Project::class, mappedBy: 'organization', cascade: ['remove'])]
    private Collection $projects;

    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: MailboxConfig::class, cascade: ['remove'])]
    private Collection $mailboxes;

    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: Contact::class, cascade: ['remove'])]
    private Collection $contacts;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->members = new ArrayCollection();
        $this->invitations = new ArrayCollection();
        $this->projects = new ArrayCollection();
        $this->mailboxes = new ArrayCollection();
        $this->contacts = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getSlug(): ?string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getLogo(): ?string { return $this->logo; }
    public function setLogo(?string $logo): static { $this->logo = $logo; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getOwner(): ?User { return $this->owner; }
    public function setOwner(?User $owner): static { $this->owner = $owner; return $this; }

    public function getMembers(): Collection { return $this->members; }
    public function getInvitations(): Collection { return $this->invitations; }
    public function getProjects(): Collection { return $this->projects; }
    public function getMailboxes(): Collection { return $this->mailboxes; }
    public function getContacts(): Collection { return $this->contacts; }

    public function isMember(User $user): bool
    {
        foreach ($this->members as $member) {
            if ($member->getUser() === $user) {
                return true;
            }
        }
        return false;
    }

    public function isAdmin(User $user): bool
    {
        foreach ($this->members as $member) {
            if ($member->getUser() === $user && $member->getRole() === OrganizationMember::ROLE_ADMIN) {
                return true;
            }
        }
        return false;
    }
}
