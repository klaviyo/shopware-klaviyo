<?php

namespace Klaviyo\Integration\Entity\Job;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class JobEntity extends Entity
{
    public const STATUS_NEW = 'new';
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_STUCK = 'stuck';

    public const HISTORICAL_EVENTS_SYNCHRONIZATION_TYPE = 'historical_events_synchronization';
    public const SUBSCRIBERS_SYNCHRONIZATION_TYPE = 'subscribers_synchronization';

    use EntityIdTrait;

    protected ?\DateTimeInterface $finishedAt = null;
    protected ?\DateTimeInterface $startedAt = null;
    protected ?string $status = null;
    protected ?string $type = null;
    protected ?bool $active = null;
    protected ?bool $createdBySchedule = null;

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
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @param string|null $status
     */
    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return bool|null
     */
    public function getActive(): ?bool
    {
        return $this->active;
    }

    /**
     * @param bool|null $active
     */
    public function setActive(?bool $active): void
    {
        $this->active = $active;
    }

    public function getCreatedBySchedule(): ?bool
    {
        return $this->createdBySchedule;
    }

    public function setCreatedBySchedule(?bool $createdBySchedule): void
    {
        $this->createdBySchedule = $createdBySchedule;
    }

    public function isStarted(): bool
    {
        return !in_array(
            $this->getStatus(),
            [self::STATUS_PENDING, self::STATUS_NEW]
        );
    }
}