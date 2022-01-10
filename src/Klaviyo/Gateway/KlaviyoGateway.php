<?php

namespace Klaviyo\Integration\Klaviyo\Gateway;

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
use Klaviyo\Integration\Klaviyo\Client\ClientResult;
use Klaviyo\Integration\Klaviyo\Gateway\Exception\ProfilesListNotFoundException;
use Klaviyo\Integration\Klaviyo\Gateway\Exception\UnableToGetListProfilesException;
use Klaviyo\Integration\Klaviyo\Gateway\Result\OrderTrackingResult;
use Klaviyo\Integration\Klaviyo\Gateway\Translator\CartEventRequestTranslator;
use Klaviyo\Integration\Klaviyo\Gateway\Translator\OrderEventRequestTranslator;
use Klaviyo\Integration\Klaviyo\Gateway\Translator\ProductEventRequestTranslator;
use Klaviyo\Integration\Klaviyo\Gateway\Translator\SubscribersToKlaviyoRequestsTranslator;
use Klaviyo\Integration\System\Tracking\Event\OrderEventInterface;
use Klaviyo\Integration\Utils\Logger\ContextHelper;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
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

    public function trackPlacedOrders(Context $context, string $channelId, array $orderEvents): OrderTrackingResult
    {
        $requestOrderIdMap = $requests = [];
        $result = new OrderTrackingResult();

        foreach ($orderEvents as $orderEvent) {
            try {
                $request = $this->orderEventRequestTranslator->translateToPlacedOrderEventRequest(
                    $context,
                    $orderEvent->getOrder()
                );
                $requestOrderIdMap[spl_object_id($request)] = $orderEvent->getOrder()->getId();
                $requests[] = $request;
            } catch (\Throwable $e) {
                $result->addFailedOrder($orderEvent->getOrder()->getId(), $e);
            }
        }

        $clientResult = $this->trackEvents($channelId, $requests);

        return $result->mergeWith(
            $this->handleClientTrackingResult($clientResult, $requestOrderIdMap, 'PlacedOrder')
        );
    }

    public function trackOrderedProducts(Context $context, string $channelId, array $orderEvents): OrderTrackingResult
    {
        $result = new OrderTrackingResult();
        $requestOrderIdMap = $requests = [];

        /** @var OrderEventInterface $event */
        foreach ($orderEvents as $event) {
            foreach ($event->getOrder()->getLineItems() as $lineItem) {
                if ($lineItem->getType() !== 'product') {
                    continue;
                }

                try {
                    $request = $this->productEventTranslator
                        ->translateToOrderedProductEventRequest($context, $lineItem, $event->getOrder());
                    $requestOrderIdMap[spl_object_id($request)] = $event->getOrder()->getId();
                    $requests[] = $request;
                } catch (\Throwable $e) {
                    $result->addFailedOrder($event->getOrder()->getId(), $e);
                }
            }
        }

        $clientResult = $this->trackEvents($channelId, $requests);

        return $result->mergeWith(
            $this->handleClientTrackingResult($clientResult, $requestOrderIdMap, 'OrderedProduct')
        );
    }

    public function trackFulfilledOrders(Context $context, string $channelId, array $orderEvents): OrderTrackingResult
    {
        $requestOrderIdMap = [];
        $requests = array_map(function (OrderEventInterface $event) use ($context, $requestOrderIdMap) {
            $request = $this->orderEventRequestTranslator->translateToFulfilledOrderEventRequest(
                $context,
                $event->getOrder(),
                $event->getEventDateTime()
            );
            $requestOrderIdMap[spl_object_id($request)] = $event->getOrder()->getId();

            return $request;
        }, $orderEvents);

        $clientResult = $this->trackEvents($channelId, $requests);

        return $this->handleClientTrackingResult($clientResult, $requestOrderIdMap, 'FulfilledOrder');
    }

    public function trackCancelledOrders(Context $context, string $channelId, array $orderEvents): OrderTrackingResult
    {
        $requestOrderIdMap = [];
        $requests = array_map(function (OrderEventInterface $event) use ($context, $requestOrderIdMap) {
            $request = $this->orderEventRequestTranslator->translateToCanceledOrderEventRequest(
                $context,
                $event->getOrder(),
                $event->getEventDateTime()
            );
            $requestOrderIdMap[spl_object_id($request)] = $event->getOrder()->getId();

            return $request;
        }, $orderEvents);

        $clientResult = $this->trackEvents($channelId, $requests);

        return $this->handleClientTrackingResult($clientResult, $requestOrderIdMap, 'CancelledOrder');
    }

    public function trackRefundedOrders(Context $context, string $channelId, array $orderEvents): OrderTrackingResult
    {
        $requestOrderIdMap = [];
        $requests = array_map(function (OrderEventInterface $event) use ($context, $requestOrderIdMap) {
            $request = $this->orderEventRequestTranslator->translateToRefundedOrderEventRequest(
                $context,
                $event->getOrder(),
                $event->getEventDateTime()
            );
            $requestOrderIdMap[spl_object_id($request)] = $event->getOrder()->getId();

            return $request;
        }, $orderEvents);

        $clientResult = $this->trackEvents($channelId, $requests);

        return $this->handleClientTrackingResult($clientResult, $requestOrderIdMap, 'RefundedOrder');
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
     * @param string $channelId
     * @param object[] $requests
     *
     * @return ClientResult
     */
    private function trackEvents(string $channelId, array $requests): ClientResult
    {
        $client = $this->clientRegistry->getClient($channelId);

        return $client->sendRequests($requests);
    }

    protected function handleClientTrackingResult(
        ClientResult $result,
        array $requestOrderIdMap,
        string $eventType
    ): OrderTrackingResult {
        $trackingResult = new OrderTrackingResult();

        foreach ($result->getRequestErrors() as $requestObjectId => $errorArray) {
            foreach ($errorArray as $error) {
                if ($failedOrderId = $requestOrderIdMap[$requestObjectId] ?? null) {
                    $trackingResult->addFailedOrder($failedOrderId, $error);
                }

                $this->logger->error(
                    sprintf(
                        'Could not track %s, reason: %s',
                        $eventType,
                        $error->getMessage()
                    ),
                    ContextHelper::createContextFromException($error)
                );
            }
        }

        return $trackingResult;
    }
}
