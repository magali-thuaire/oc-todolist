<?php

namespace App\Entity;

use Datetime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table]
class Task
{
    #[ORM\Column]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime')]
    private Datetime $createdAt;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'task.title.not_blank')]
    private ?string $title = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'task.content.not_blank')]
    private ?string $content = null;

    #[ORM\Column]
    private bool $isDone = false;

    #[ORM\ManyToOne(inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $owner = null;

    public const DONE_ACTION = 'Marquer comme faite';
    public const UNDONE_ACTION = 'Marquer comme non terminée';

    public function __construct()
    {
        $this->createdAt = new Datetime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): Datetime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(Datetime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function isDone(): bool
    {
        return $this->isDone;
    }

    public function setIsDone(bool $isDone): self
    {
        $this->isDone = $isDone;

        return $this;
    }

    public function toggle(): self
    {
        $this->isDone = !$this->isDone;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }
}
