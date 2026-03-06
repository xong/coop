<?php

namespace App\Entity;

use App\Repository\ContactRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContactRepository::class)]
#[ORM\Table(name: 'contacts')]
#[ORM\HasLifecycleCallbacks]
class Contact
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'contacts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Organization $organization = null;

    #[ORM\Column(length: 100)]
    private string $firstName = '';

    #[ORM\Column(length: 100)]
    private string $lastName = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $company = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $position = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $website = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToMany(mappedBy: 'contact', targetEntity: ContactField::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['label' => 'ASC'])]
    private Collection $customFields;

    #[ORM\OneToMany(mappedBy: 'contact', targetEntity: ContactComment::class, cascade: ['remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $comments;

    #[ORM\ManyToMany(targetEntity: Project::class)]
    #[ORM\JoinTable(name: 'contact_projects')]
    private Collection $linkedProjects;

    #[ORM\ManyToMany(targetEntity: Topic::class)]
    #[ORM\JoinTable(name: 'contact_topics')]
    private Collection $linkedTopics;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->customFields = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->linkedProjects = new ArrayCollection();
        $this->linkedTopics = new ArrayCollection();
    }

    #[ORM\PreUpdate]
    public function onUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getOrganization(): ?Organization { return $this->organization; }
    public function setOrganization(?Organization $organization): static { $this->organization = $organization; return $this; }
    public function getFirstName(): string { return $this->firstName; }
    public function setFirstName(string $firstName): static { $this->firstName = $firstName; return $this; }
    public function getLastName(): string { return $this->lastName; }
    public function setLastName(string $lastName): static { $this->lastName = $lastName; return $this; }
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): static { $this->email = $email; return $this; }
    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(?string $phone): static { $this->phone = $phone; return $this; }
    public function getCompany(): ?string { return $this->company; }
    public function setCompany(?string $company): static { $this->company = $company; return $this; }
    public function getPosition(): ?string { return $this->position; }
    public function setPosition(?string $position): static { $this->position = $position; return $this; }
    public function getWebsite(): ?string { return $this->website; }
    public function setWebsite(?string $website): static { $this->website = $website; return $this; }
    public function getAddress(): ?string { return $this->address; }
    public function setAddress(?string $address): static { $this->address = $address; return $this; }
    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): static { $this->notes = $notes; return $this; }
    public function getAvatar(): ?string { return $this->avatar; }
    public function setAvatar(?string $avatar): static { $this->avatar = $avatar; return $this; }
    public function getCreatedBy(): ?User { return $this->createdBy; }
    public function setCreatedBy(?User $createdBy): static { $this->createdBy = $createdBy; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    /** @return Collection<int, ContactField> */
    public function getCustomFields(): Collection { return $this->customFields; }

    public function addCustomField(ContactField $field): static
    {
        if (!$this->customFields->contains($field)) {
            $this->customFields->add($field);
            $field->setContact($this);
        }
        return $this;
    }

    public function removeCustomField(ContactField $field): static
    {
        $this->customFields->removeElement($field);
        return $this;
    }

    /** @return Collection<int, ContactComment> */
    public function getComments(): Collection { return $this->comments; }

    /** @return Collection<int, Project> */
    public function getLinkedProjects(): Collection { return $this->linkedProjects; }

    public function addLinkedProject(Project $project): static
    {
        if (!$this->linkedProjects->contains($project)) {
            $this->linkedProjects->add($project);
        }
        return $this;
    }

    public function removeLinkedProject(Project $project): static
    {
        $this->linkedProjects->removeElement($project);
        return $this;
    }

    /** @return Collection<int, Topic> */
    public function getLinkedTopics(): Collection { return $this->linkedTopics; }

    public function addLinkedTopic(Topic $topic): static
    {
        if (!$this->linkedTopics->contains($topic)) {
            $this->linkedTopics->add($topic);
        }
        return $this;
    }

    public function removeLinkedTopic(Topic $topic): static
    {
        $this->linkedTopics->removeElement($topic);
        return $this;
    }

    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    public function getInitials(): string
    {
        return strtoupper(($this->firstName[0] ?? '') . ($this->lastName[0] ?? ''));
    }
}
