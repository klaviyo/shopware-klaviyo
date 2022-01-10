<?php declare(strict_types=1);

namespace Klaviyo\Integration\System\Tracking\Event;

class OrderEventBag
{
    private array $events = [];

    public function add(OrderEventInterface $event)
    {
        $this->events[] = $event;
    }

    /**
     * @return OrderEventInterface[]
     */
    public function all(): array
    {
        return $this->events;
    }
}
