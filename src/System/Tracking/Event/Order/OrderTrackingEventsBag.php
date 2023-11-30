<?php

declare(strict_types=1);

namespace Klaviyo\Integration\System\Tracking\Event\Order;

class OrderTrackingEventsBag
{
    private array $channelOrderMap = [];

    public function add(OrderEventInterface $orderEvent): void
    {
        $order = $orderEvent->getOrder();
        $this->channelOrderMap[$order->getSalesChannelId()][$order->getId()] = $orderEvent;
    }

    /**
     * @return array<string, OrderEventInterface[]>
     */
    public function all(): array
    {
        return $this->channelOrderMap;
    }
}
