<?php

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\Common;

class EventTrackingRequest
{
    private string $eventId;
    private \DateTimeInterface $time;
    private ?CustomerProperties $customerProperties;

    public function __construct(string $eventId, \DateTimeInterface $time, ?CustomerProperties $customerProperties)
    {
        $this->eventId = $eventId;
        $this->time = $time;
        $this->customerProperties = $customerProperties;
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getTime(): \DateTimeInterface
    {
        return $this->time;
    }

    public function getCustomerProperties(): ?CustomerProperties
    {
        return $this->customerProperties;
    }
}