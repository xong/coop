<?php

namespace App\Entity;

use App\Repository\ContactCommentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContactCommentRepository::class)]
#[ORM\Table(name: 'contact_comments')]
class ContactComment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Contact $contact = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $author = null;

    #[ORM\Column(type: Types::TEXT)]
    private string $content = '';

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getContact(): ?Contact { return $this->contact; }
    public function setContact(?Contact $contact): static { $this->contact = $contact; return $this; }
    public function getAuthor(): ?User { return $this->author; }
    public function setAuthor(?User $author): static { $this->author = $author; return $this; }
    public function getContent(): string { return $this->content; }
    public function setContent(string $content): static { $this->content = $content; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
