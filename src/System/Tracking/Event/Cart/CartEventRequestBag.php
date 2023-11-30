<?php

declare(strict_types=1);

namespace Klaviyo\Integration\System\Tracking\Event\Cart;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\Common\EventTrackingRequest;

class CartEventRequestBag
{
    private array $channelRequestMap = [];

    public function add(EventTrackingRequest $request, string $channelId): void
    {
        $this->channelRequestMap[$channelId][] = $request;
    }

    /**
     * @return array<string, EventTrackingRequest[]>
     */
    public function all(): array
    {
        return $this->channelRequestMap;
    }
}
