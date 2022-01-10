<?php

namespace Klaviyo\Integration\Entity\Job;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class JobEntity extends Entity
{
    use EntityIdTrait;

    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_STUCK = 'stuck';

    public const TYPE_FULL_SUBSCRIBER_SYNC = 'full-subscriber-sync';
    public const TYPE_FULL_ORDERS_SYNC = 'full-order-sync';
    public const TYPE_SUBSCRIBER_SYNC = 'subscriber-sync';
    public const TYPE_ORDERS_SYNC = 'order-sync';
    public const TYPE_ORDERS_EVENTS_SYNC = 'order-events-sync';

    public const HISTORICAL_EVENTS_SYNCHRONIZATION_TYPE = 'historical_events_synchronization';
    public const SUBSCRIBERS_SYNCHRONIZATION_TYPE = 'subscribers_synchronization';

    protected ?string $parentId = null;
    protected string $status;
    protected string $type;
    protected string $name;
    protected ?string $message = null;
    protected ?\DateTimeInterface $startedAt = null;
    protected ?\DateTimeInterface $finishedAt = null;

    /**
     * @return string|null
     */
    public function getParentId(): ?string
    {
        return $this->parentId;
    }

    /**
     * @param string|null $parentId
     */
    public function setParentId(?string $parentId): void
    {
        $this->parentId = $parentId;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * @param string|null $message
     */
    public function setMessage(?string $message): void
    {
        $this->message = $message;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getStartedAt(): ?\DateTimeInterface
    {
        return $this->startedAt;
    }

    /**
     * @param \DateTimeInterface|null $startedAt
     */
    public function setStartedAt(?\DateTimeInterface $startedAt): void
    {
        $this->startedAt = $startedAt;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getFinishedAt(): ?\DateTimeInterface
    {
        return $this->finishedAt;
    }

    /**
     * @param \DateTimeInterface|null $finishedAt
     */
    public function setFinishedAt(?\DateTimeInterface $finishedAt): void
    {
        $this->finishedAt = $finishedAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'parentId' => $this->getParentId(),
            'status' => $this->getStatus(),
            'type' => $this->getType(),
            'name' => $this->getName(),
            'message' => $this->getMessage(),
            'startedAt' => $this->getStartedAt(),
            'finishedAt' => $this->getFinishedAt(),
            'createdAt' => $this->getCreatedAt(),
        ];
    }
}
