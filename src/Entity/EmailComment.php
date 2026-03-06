<?php

namespace App\Entity;

use App\Repository\EmailCommentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmailCommentRepository::class)]
#[ORM\Table(name: 'email_comments')]
class EmailComment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?IncomingEmail $email = null;

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
    public function getEmail(): ?IncomingEmail { return $this->email; }
    public function setEmail(?IncomingEmail $email): static { $this->email = $email; return $this; }
    public function getAuthor(): ?User { return $this->author; }
    public function setAuthor(?User $author): static { $this->author = $author; return $this; }
    public function getContent(): string { return $this->content; }
    public function setContent(string $content): static { $this->content = $content; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
