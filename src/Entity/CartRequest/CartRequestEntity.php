<?php declare(strict_types=1);

namespace Klaviyo\Integration\Entity\CartRequest;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class CartRequestEntity extends Entity
{
    use EntityIdTrait;

    protected string $salesChannelId;
    protected string $serializedRequest;

    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }

    public function setSalesChannelId(string $salesChannelId): void
    {
        $this->salesChannelId = $salesChannelId;
    }

    public function getSerializedRequest(): string
    {
        return $this->serializedRequest;
    }

    public function setSerializedRequest(string $serializedRequest): void
    {
        $this->serializedRequest = $serializedRequest;
    }
}
