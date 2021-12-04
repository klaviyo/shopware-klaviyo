<?php

namespace Klaviyo\Integration\Klaviyo\Gateway;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\Common\EventTrackingRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\AddMembersToList\AddProfilesToListResponse;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common\ProfileContactInfoCollection;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common\ProfileInfo;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetListProfiles\GetListProfilesRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetListProfiles\GetListProfilesResponse;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetLists\DTO\ProfilesListInfo;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetLists\GetProfilesListsRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetLists\GetProfilesListsResponse;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\RemoveProfilesFromList\RemoveProfilesFromListRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\RemoveProfilesFromList\RemoveProfilesFromListResponse;
use Klaviyo\Integration\Klaviyo\Gateway\Exception\ProfilesListNotFoundException;
use Klaviyo\Integration\Klaviyo\Gateway\Exception\UnableToGetListProfilesException;
use Klaviyo\Integration\Klaviyo\Gateway\Translator\CartEventRequestTranslator;
use Klaviyo\Integration\Klaviyo\Gateway\Translator\OrderEventRequestTranslator;
use Klaviyo\Integration\Klaviyo\Gateway\Translator\ProductEventRequestTranslator;
use Klaviyo\Integration\Klaviyo\Gateway\Translator\SubscribersToKlaviyoRequestsTranslator;
use Klaviyo\Integration\Utils\Logger\ContextHelper;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class KlaviyoGateway
{
    private ClientRegistry $clientRegistry;
    private OrderEventRequestTranslator $orderEventRequestTranslator;
    private ProductEventRequestTranslator $productEventTranslator;
    private CartEventRequestTranslator $cartEventRequestTranslator;
    private SubscribersToKlaviyoRequestsTranslator $subscribersTranslator;
    private LoggerInterface $logger;

    public function __construct(
        ClientRegistry $clientRegistry,
        OrderEventRequestTranslator $placedOrderEventRequestTranslator,
        ProductEventRequestTranslator $productEventTranslator,
        CartEventRequestTranslator $cartEventRequestTranslator,
        SubscribersToKlaviyoRequestsTranslator $subscribersTranslator,
        LoggerInterface $logger
    ) {
        $this->clientRegistry = $clientRegistry;
        $this->orderEventRequestTranslator = $placedOrderEventRequestTranslator;
        $this->productEventTranslator = $productEventTranslator;
        $this->cartEventRequestTranslator = $cartEventRequestTranslator;
        $this->subscribersTranslator = $subscribersTranslator;
        $this->logger = $logger;
    }

    public function trackPlacedOrder(
        Context $context,
        SalesChannelEntity $salesChannelEntity,
        OrderEntity $orderEntity
    ): bool {
        try {
            $placedOrderEventRequest = $this->orderEventRequestTranslator
                ->translateToPlacedOrderEventRequest($context, $orderEntity);

            $this->trackEvent($salesChannelEntity, $placedOrderEventRequest);

            return true;
        } catch (\Throwable $exception) {
            $this->logger->error(
                sprintf(
                    'Could not track PlacedOrderEvent, reason: %s',
                    $exception->getMessage()
                ),
                ContextHelper::createContextFromException($exception)
            );

            return false;
        }
    }

    public function trackOrderedProducts(
        Context $context,
        SalesChannelEntity $salesChannelEntity,
        OrderEntity $orderEntity
    ): bool {
        $isSuccess = true;
        /** @var OrderLineItemEntity $lineItem */
        foreach ($orderEntity->getLineItems() as $lineItem) {
            if ($lineItem->getType() !== 'product') {
                continue;
            }

            $trackEventResult = $this->trackOrderedProduct(
                $context,
                $salesChannelEntity,
                $lineItem,
                $orderEntity
            );
            if (!$trackEventResult) {
                $isSuccess = false;
            }
        }

        return $isSuccess;
    }

    public function trackOrderedProduct(
        Context $context,
        SalesChannelEntity $salesChannelEntity,
        OrderLineItemEntity $lineItem,
        OrderEntity $orderEntity
    ): bool {
        try {
            $event = $this->productEventTranslator
                ->translateToOrderedProductEventRequest($context, $lineItem, $orderEntity);

            $this->trackEvent($salesChannelEntity, $event);

            return true;
        } catch (\Throwable $exception) {
            $this->logger->error(
                sprintf(
                    'Could not track OrderedProduct, reason: %s',
                    $exception->getMessage()
                ),
                ContextHelper::createContextFromException($exception)
            );

            return false;
        }
    }

    public function trackFulfilledOrder(
        Context $context,
        SalesChannelEntity $salesChannelEntity,
        OrderEntity $orderEntity,
        \DateTimeInterface $eventHappenedDateTime
    ): bool {
        try {
            $fulfilledOrderEventRequest = $this->orderEventRequestTranslator
                ->translateToFulfilledOrderEventRequest($context, $orderEntity, $eventHappenedDateTime);

            $this->trackEvent($salesChannelEntity, $fulfilledOrderEventRequest);

            return true;
        } catch (\Throwable $exception) {
            $this->logger->error(
                sprintf(
                    'Could not track FulfilledOrder, reason: %s',
                    $exception->getMessage()
                ),
                ContextHelper::createContextFromException($exception)
            );

            return false;
        }
    }

    public function trackCancelledOrder(
        Context $context,
        SalesChannelEntity $salesChannelEntity,
        OrderEntity $orderEntity,
        \DateTimeInterface $eventHappenedDateTime
    ): bool {
        try {
            $canceledOrderEventRequest = $this->orderEventRequestTranslator
                ->translateToCanceledOrderEventRequest($context, $orderEntity, $eventHappenedDateTime);

            $this->trackEvent($salesChannelEntity, $canceledOrderEventRequest);

            return true;
        } catch (\Throwable $exception) {
            $this->logger->error(
                sprintf(
                    'Could not track CancelledOrder, reason: %s',
                    $exception->getMessage()
                ),
                ContextHelper::createContextFromException($exception)
            );

            return false;
        }
    }

    public function trackRefundedOrder(
        Context $context,
        SalesChannelEntity $salesChannelEntity,
        OrderEntity $orderEntity,
        \DateTimeInterface $eventHappenedDateTime
    ): bool {
        try {
            $refundedOrderEventRequest = $this->orderEventRequestTranslator
                ->translateToRefundedOrderEventRequest($context, $orderEntity, $eventHappenedDateTime);

            $this->trackEvent($salesChannelEntity, $refundedOrderEventRequest);

            return true;
        } catch (\Throwable $exception) {
            $this->logger->error(
                sprintf(
                    'Could not track CancelledOrder, reason: %s',
                    $exception->getMessage()
                ),
                ContextHelper::createContextFromException($exception)
            );

            return false;
        }
    }

    public function trackAddedToCart(
        SalesChannelContext $context,
        Cart $cart,
        LineItem $lineItem
    ): bool {
        try {
            $now = new \DateTime('now', new \DateTimeZone('UTC'));
            $eventRequest = $this->cartEventRequestTranslator
                ->translateToAddedToCartEventRequest($context, $cart, $lineItem, $now);

            $salesChannelEntity = $context->getSalesChannel();
            $this->trackEvent($salesChannelEntity, $eventRequest);

            return true;
        } catch (\Throwable $exception) {
            $this->logger->error(
                sprintf(
                    'Could not track AddedToCartEvent, reason: %s',
                    $exception->getMessage()
                ),
                ContextHelper::createContextFromException($exception)
            );

            return false;
        }
    }

    /**
     * @param SalesChannelEntity $salesChannelEntity
     * @param string $listId
     *
     * @return \Generator|ProfileInfo[]
     */
    public function getKlaviyoListMembersGenerator(SalesChannelEntity $salesChannelEntity, string $listId): \Generator
    {
        do {
            $members = $this->getKlaviyoListMembersResponse($salesChannelEntity, $listId, null);

            foreach ($members->getProfiles() as $profile) {
                yield $profile;
            }
        } while ($members->getLastRequestCursorMarker() !== null && $members->getProfiles()->count() > 0);
    }

    /**
     * @param SalesChannelEntity $salesChannelEntity
     * @param string $listId
     * @param int|null $cursorMarker
     *
     * @return GetListProfilesResponse
     * @throws UnableToGetListProfilesException
     */
    private function getKlaviyoListMembersResponse(
        SalesChannelEntity $salesChannelEntity,
        string $listId,
        ?int $cursorMarker
    ): GetListProfilesResponse {
        try {
            $request = new GetListProfilesRequest($listId, $cursorMarker);

            /** @var GetListProfilesResponse $result */
            $result = $this->clientRegistry
                ->getClient($salesChannelEntity)
                ->sendRequest($request);
        } catch (\Throwable $exception) {
            $message = sprintf(
                'Could not get Klaviyo profiles from list, reason: %s',
                $exception->getMessage()
            );
            $this->logger->error(
                $message,
                ContextHelper::createContextFromException($exception)
            );

            throw new UnableToGetListProfilesException($message);
        }

        if (!$result->isSuccess()) {
            $message = sprintf(
                'Could not get Klaviyo profiles from list, reason: %s',
                $result->getErrorDetails()
            );
            $this->logger->error($message);

            throw new UnableToGetListProfilesException($message);
        }

        return $result;
    }

    public function addToKlaviyoProfilesList(
        SalesChannelEntity $salesChannelEntity,
        NewsletterRecipientCollection $newsletterRecipientCollection,
        string $profilesListId
    ): bool {
        try {
            $request = $this->subscribersTranslator
                ->translateToAddProfilesRequest($newsletterRecipientCollection, $profilesListId);

            /** @var AddProfilesToListResponse $result */
            $result = $this->clientRegistry
                ->getClient($salesChannelEntity)
                ->sendRequest($request);
            if (!$result->isSuccess()) {
                $this->logger->error(
                    sprintf(
                        'Could not add Shopware subscribers to Klaviyo profiles list, reason: %s',
                        $result->getErrorDetails()
                    )
                );
            }

            return $result->isSuccess();
        } catch (\Throwable $exception) {
            $this->logger->error(
                sprintf(
                    'Could not add Shopware subscribers to Klaviyo profiles list, reason: %s',
                    $exception->getMessage()
                ),
                ContextHelper::createContextFromException($exception)
            );

            return false;
        }
    }

    public function removeKlaviyoSubscribersFromList(
        SalesChannelEntity $salesChannelEntity,
        ProfileContactInfoCollection $profileInfoCollection,
        string $profilesListId
    ): bool {
        try {
            $request = new RemoveProfilesFromListRequest(
                $profilesListId,
                $profileInfoCollection
            );

            /** @var RemoveProfilesFromListResponse $result */
            $result = $this->clientRegistry
                ->getClient($salesChannelEntity)
                ->sendRequest($request);

            if (!$result->isSuccess()) {
                $this->logger->error(
                    sprintf(
                        'Could not remove Shopware subscribers from Klaviyo profiles list, reason: %s',
                        $result->getErrorDetails()
                    )
                );
            }

            return $result->isSuccess();
        } catch (\Throwable $exception) {
            $this->logger->error(
                sprintf(
                    'Could not remove Shopware subscribers from Klaviyo profiles list, reason: %s',
                    $exception->getMessage()
                ),
                ContextHelper::createContextFromException($exception)
            );

            return false;
        }
    }

    /**
     * @param SalesChannelEntity $salesChannelEntity
     * @param string $profilesListName
     *
     * @return string
     * @throws \Klaviyo\Integration\Klaviyo\Client\Exception\ClientException
     * @throws \Throwable
     */
    public function getListIdByListName(
        SalesChannelEntity $salesChannelEntity,
        string $profilesListName
    ): string {
        /** @var GetProfilesListsResponse $result */
        $result = $this->clientRegistry
            ->getClient($salesChannelEntity)
            ->sendRequest(new GetProfilesListsRequest());

        if (!$result->isSuccess()) {
            throw new ProfilesListNotFoundException(
                sprintf('Could not get Profiles list from Klaviyo. Reason: %s', $result->getErrorDetails())
            );
        }

        /** @var ProfilesListInfo $list */
        foreach ($result->getLists() as $list) {
            if ($list->getName() === $profilesListName) {
                return $list->getId();
            }
        }

        throw new ProfilesListNotFoundException(
            sprintf('Profiles list[name: "%s"] was not found', $profilesListName)
        );
    }

    /**
     * @param SalesChannelEntity $salesChannelEntity
     * @param EventTrackingRequest $request
     *
     * @throws \Klaviyo\Integration\Klaviyo\Client\Exception\ClientException
     * @throws \Throwable
     */
    private function trackEvent(SalesChannelEntity $salesChannelEntity, EventTrackingRequest $request): void
    {
        $this->clientRegistry
            ->getClient($salesChannelEntity)
            ->sendRequest($request);
    }
}
