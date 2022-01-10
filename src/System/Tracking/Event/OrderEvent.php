<?php declare(strict_types=1);

namespace Klaviyo\Integration\System\Tracking\Event;

use Shopware\Core\Checkout\Order\OrderEntity;

class OrderEvent implements OrderEventInterface
{
    private OrderEntity $order;
    private \DateTimeInterface $when;

    public function __construct(
        OrderEntity $order,
        ?\DateTimeInterface $when = null
    ) {

        $this->order = $order;
        $this->when = $when ?? new \DateTime('now', new \DateTimeZone('UTC'));
    }

    public function getOrder(): OrderEntity
    {
        return $this->order;
    }

    public function getEventDateTime(): \DateTimeInterface
    {
        return $this->when;
    }
}
