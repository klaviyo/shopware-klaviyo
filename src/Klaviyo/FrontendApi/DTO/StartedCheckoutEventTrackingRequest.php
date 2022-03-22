<?php

namespace Klaviyo\Integration\Klaviyo\FrontendApi\DTO;

class StartedCheckoutEventTrackingRequest implements \JsonSerializable
{
    private string $eventId;
    private string $checkoutUrl;
    private string $checkoutTotal;
    private CheckoutLineItemInfoCollection $lineItemInfoCollection;

    public function __construct(
        string $eventId,
        string $checkoutUrl,
        string $checkoutTotal,
        CheckoutLineItemInfoCollection $lineItemInfoCollection
    ) {
        $this->eventId = $eventId;
        $this->checkoutUrl = $checkoutUrl;
        $this->checkoutTotal = $checkoutTotal;
        $this->lineItemInfoCollection = $lineItemInfoCollection;
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getCheckoutUrl(): string
    {
        return $this->checkoutUrl;
    }

    public function getCheckoutTotal(): string
    {
        return $this->checkoutTotal;
    }

    public function getLineItemInfoCollection(): CheckoutLineItemInfoCollection
    {
        return $this->lineItemInfoCollection;
    }

    public function jsonSerialize()
    {
        $categories = [];
        $itemNames = [];
        /** @var CheckoutLineItemInfo $lineItem */
        foreach ($this->getLineItemInfoCollection() as $lineItem) {
            array_push($categories, ...$lineItem->getCategoryNames());
            $itemNames[] = $lineItem->getName();
        }
        $categories = array_unique($categories);
        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        return [
            '$event_id' => $this->getEventId() . '_' . $now->getTimestamp(),
            '$value' => $this->getCheckoutTotal(),
            'CheckoutURL' => $this->getCheckoutUrl(),
            'ItemNames' => $itemNames,
            'Categories' => $this->getCheckoutUrl(),
            'Items' => $this->getLineItemInfoCollection()->getElements()
        ];
    }
}