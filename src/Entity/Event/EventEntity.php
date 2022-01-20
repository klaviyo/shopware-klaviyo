<?php declare(strict_types=1);

namespace Klaviyo\Integration\Entity\Event;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class EventEntity extends Entity
{
    use EntityIdTrait;

    const TYPE_ORDER_PLACED = 'order-placed';
    const TYPE_ORDER_FULFILLED = 'order-fulfilled';
    const TYPE_ORDER_CANCELED = 'order-canceled';
    const TYPE_ORDER_REFUNDED = 'order-refunded';

    protected string $type;
    protected string $metadata;
    protected string $entityId;
    protected string $salesChannelId;
    protected \DateTimeInterface $happenedAt;

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
    public function getMetadata(): string
    {
        return $this->metadata;
    }

    /**
     * @param string $type
     */
    public function setMetadata(string $metadata): void
    {
        $this->metadata = $metadata;
    }

    /**
     * @return string
     */
    public function getEntityId(): string
    {
        return $this->entityId;
    }

    /**
     * @param string $entityId
     */
    public function setEntityId(string $entityId): void
    {
        $this->entityId = $entityId;
    }

    /**
     * @return string
     */
    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }

    /**
     * @param string $salesChannelId
     */
    public function setSalesChannelId(string $salesChannelId): void
    {
        $this->salesChannelId = $salesChannelId;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getHappenedAt(): \DateTimeInterface
    {
        return $this->happenedAt;
    }

    /**
     * @param \DateTimeInterface $happenedAt
     */
    public function setHappenedAt(\DateTimeInterface $happenedAt): void
    {
        $this->happenedAt = $happenedAt;
    }
}
