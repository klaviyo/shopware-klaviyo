<?php

namespace Klaviyo\Integration\System\Tracking\Event\Order;

use Shopware\Core\Checkout\Order\OrderEntity;

interface OrderEventInterface
{
    public function getOrder(): OrderEntity;

    public function getEventDateTime(): \DateTimeInterface;
}
