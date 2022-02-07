<?php declare(strict_types=1);

namespace Od\Scheduler\Entity\Job;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class JobEntity extends Entity
{
    const TYPE_PENDING = 'pending';
    const TYPE_RUNNING = 'running';
    const TYPE_SUCCEED = 'succeed';
    const TYPE_FAILED = 'error';

    use EntityIdTrait;

    protected ?string $parentId = null;
    protected string $status;
    protected string $type;
    protected string $name;
    protected ?string $message = null;
    protected ?\DateTimeInterface $startedAt = null;
    protected ?\DateTimeInterface $finishedAt = null;

    public function getParentId(): ?string
    {
        return $this->parentId;
    }

    public function setParentId(?string $parentId): void
    {
        $this->parentId = $parentId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): void
    {
        $this->message = $message;
    }

    public function getStartedAt(): ?\DateTimeInterface
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeInterface $startedAt): void
    {
        $this->startedAt = $startedAt;
    }

    public function getFinishedAt(): ?\DateTimeInterface
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?\DateTimeInterface $finishedAt): void
    {
        $this->finishedAt = $finishedAt;
    }
}
