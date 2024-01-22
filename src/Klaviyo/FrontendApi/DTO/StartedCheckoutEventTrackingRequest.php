<?php

namespace Klaviyo\Integration\Klaviyo\FrontendApi\DTO;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\Common\CustomerProperties;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\Common\EventTrackingRequest;

class StartedCheckoutEventTrackingRequest extends EventTrackingRequest implements \JsonSerializable
{
    private string $eventId;
    private string $checkoutUrl;
    private string $checkoutTotal;
    private CheckoutLineItemInfoCollection $lineItemInfoCollection;

    public function __construct(
        string $eventId,
        string $checkoutUrl,
        string $checkoutTotal,
        CheckoutLineItemInfoCollection $lineItemInfoCollection,
        \DateTimeInterface $time,
        ?CustomerProperties $customerProperties
    ) {
        parent::__construct($eventId, $time, $customerProperties);

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

    public function jsonSerialize(): array
    {
        $categories = [];
        $itemNames = [];
        /** @var CheckoutLineItemInfo $lineItem */
        foreach ($this->getLineItemInfoCollection() as $lineItem) {
            array_push($categories, ...$lineItem->getCategoryNames());
            $itemNames[] = $lineItem->getName();
        }
        $categories = array_unique($categories);

        return [
            'data' => [
                'type' => 'event',
                'attributes' => [
                    'time' => $this->getTime()->format('Y-m-d\TH:i:s'),
                    'value' => $this->getCheckoutTotal(),
                    'unique_id' => $this->getEventId() . '_' . $this->getTime()->getTimestamp(),
                    'properties' => [
                        'startedCheckoutValue' => $this->getCheckoutTotal(),
                        'CheckoutURL' => $this->getCheckoutUrl(),
                        'ItemNames' => $itemNames,
                        'Categories' => $categories,
                        'Items' => $this->getLineItemInfoCollection()->getElements(),
                    ],
                    'metric' => [
                        'data' => [
                            'type' => 'metric',
                            'attributes' => [
                                'name' => 'Started Checkout'
                            ]
                        ]
                    ],
                    'profile' => [
                        'data' => [
                            'type' => 'profile',
                            'id' => '',
                            'attributes' => [
                                'email' => $this->getCustomerProperties()->getEmail()
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
