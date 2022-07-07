<?php

namespace App\Entity;

use App\Repository\TaskRepository;
use Datetime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
#[ORM\Table]
class Task
{
    use TimestampableEntity;

    #[ORM\Column]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'task.title.not_blank')]
    private ?string $title = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'task.content.not_blank')]
    private ?string $content = null;

    #[ORM\Column]
    private bool $isDone = false;

    #[ORM\ManyToOne(inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $owner = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $doneAt;

    public const DONE_ACTION = 'Marquer comme faite';
    public const UNDONE_ACTION = 'Marquer comme non terminée';
    public const ANONYMOUS_TASK = 'anonyme';

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
        $this->doneAt = null;

        if ($this->isDone()) {
            $this->doneAt = new Datetime();
        }

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

    public function getDoneAt(): ?DateTimeInterface
    {
        return $this->doneAt;
    }

    public function setDoneAt(?DateTimeInterface $doneAt): self
    {
        $this->doneAt = $doneAt;

        return $this;
    }
}
