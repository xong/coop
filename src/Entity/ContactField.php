<?php

namespace App\Entity;

use App\Repository\ContactFieldRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContactFieldRepository::class)]
#[ORM\Table(name: 'contact_fields')]
class ContactField
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'customFields')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Contact $contact = null;

    #[ORM\Column(length: 100)]
    private string $label = '';

    #[ORM\Column(length: 500)]
    private string $value = '';

    public function getId(): ?int { return $this->id; }
    public function getContact(): ?Contact { return $this->contact; }
    public function setContact(?Contact $contact): static { $this->contact = $contact; return $this; }
    public function getLabel(): string { return $this->label; }
    public function setLabel(string $label): static { $this->label = $label; return $this; }
    public function getValue(): string { return $this->value; }
    public function setValue(string $value): static { $this->value = $value; return $this; }
}
