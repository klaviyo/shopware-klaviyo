<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Gateway;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\ExcludedSubscribers\GetExcludedSubscribers;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\AddMembersToList\AddProfilesToListResponse;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common\ProfileContactInfoCollection;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\RemoveProfilesFromList\RemoveProfilesFromListRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\RemoveProfilesFromList\RemoveProfilesFromListResponse;
use Klaviyo\Integration\Klaviyo\Client\ClientResult;
use Klaviyo\Integration\Klaviyo\Gateway\Domain\Profile\Search\ProfileIdSearchResult;
use Klaviyo\Integration\Klaviyo\Gateway\Domain\Profile\Search\Strategy\SearchStrategyInterface;
use Klaviyo\Integration\Klaviyo\Gateway\Exception\ProfilesListNotFoundException;
use Klaviyo\Integration\Klaviyo\Gateway\Result\OrderTrackingResult;
use Klaviyo\Integration\Klaviyo\Gateway\Translator\CartEventRequestTranslator;
use Klaviyo\Integration\Klaviyo\Gateway\Translator\IdentifyProfileRequestTranslator;
use Klaviyo\Integration\Klaviyo\Gateway\Translator\OrderEventRequestTranslator;
use Klaviyo\Integration\Klaviyo\Gateway\Translator\ProductEventRequestTranslator;
use Klaviyo\Integration\Klaviyo\Gateway\Translator\SubscribersToKlaviyoRequestsTranslator;
use Klaviyo\Integration\Klaviyo\Gateway\Translator\UpdateProfileRequestTranslator;
use Klaviyo\Integration\System\Tracking\Event\Order\OrderEventInterface;
use Klaviyo\Integration\Utils\Logger\ContextHelper;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Customer\CustomerCollection;
use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientCollection;
use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientEntity as Recipient;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class KlaviyoGateway
{
    private ClientRegistry $clientRegistry;
    private OrderEventRequestTranslator $orderEventRequestTranslator;
    private ProductEventRequestTranslator $productEventTranslator;
    private CartEventRequestTranslator $cartEventRequestTranslator;
    private SubscribersToKlaviyoRequestsTranslator $subscribersTranslator;
    private IdentifyProfileRequestTranslator $identifyProfileRequestTranslator;
    private SearchStrategyInterface $profileIdSearchStrategy;
    private UpdateProfileRequestTranslator $updateProfileRequestTranslator;
    private LoggerInterface $logger;

    public function __construct(
        ClientRegistry $clientRegistry,
        OrderEventRequestTranslator $placedOrderEventRequestTranslator,
        ProductEventRequestTranslator $productEventTranslator,
        CartEventRequestTranslator $cartEventRequestTranslator,
        SubscribersToKlaviyoRequestsTranslator $subscribersTranslator,
        IdentifyProfileRequestTranslator $identifyProfileRequestTranslator,
        SearchStrategyInterface $profileIdSearchStrategy,
        UpdateProfileRequestTranslator $updateProfileRequestTranslator,
        LoggerInterface $logger
    ) {
        $this->clientRegistry = $clientRegistry;
        $this->orderEventRequestTranslator = $placedOrderEventRequestTranslator;
        $this->productEventTranslator = $productEventTranslator;
        $this->cartEventRequestTranslator = $cartEventRequestTranslator;
        $this->subscribersTranslator = $subscribersTranslator;
        $this->identifyProfileRequestTranslator = $identifyProfileRequestTranslator;
        $this->profileIdSearchStrategy = $profileIdSearchStrategy;
        $this->updateProfileRequestTranslator = $updateProfileRequestTranslator;
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
            foreach ($event->getOrder()->getLineItems() ?? [] as $lineItem) {
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
        $requestOrderIdMap = $requests = [];
        $result = new OrderTrackingResult();

        /** @var OrderEventInterface $orderEvent */
        foreach ($orderEvents as $orderEvent) {
            try {
                $request = $this->orderEventRequestTranslator->translateToFulfilledOrderEventRequest(
                    $context,
                    $orderEvent->getOrder(),
                    $orderEvent->getEventDateTime()
                );
                $requestOrderIdMap[spl_object_id($request)] = $orderEvent->getOrder()->getId();
                $requests[] = $request;
            } catch (\Throwable $e) {
                $result->addFailedOrder($orderEvent->getOrder()->getId(), $e);
            }
        }

        $clientResult = $this->trackEvents($channelId, $requests);

        return $result->mergeWith(
            $this->handleClientTrackingResult($clientResult, $requestOrderIdMap, 'FulfilledOrder')
        );
    }

    public function trackCancelledOrders(Context $context, string $channelId, array $orderEvents): OrderTrackingResult
    {
        $requestOrderIdMap = $requests = [];
        $result = new OrderTrackingResult();

        /** @var OrderEventInterface $orderEvent */
        foreach ($orderEvents as $orderEvent) {
            try {
                $request = $this->orderEventRequestTranslator->translateToCanceledOrderEventRequest(
                    $context,
                    $orderEvent->getOrder(),
                    $orderEvent->getEventDateTime()
                );
                $requestOrderIdMap[spl_object_id($request)] = $orderEvent->getOrder()->getId();
                $requests[] = $request;
            } catch (\Throwable $e) {
                $result->addFailedOrder($orderEvent->getOrder()->getId(), $e);
            }
        }

        $clientResult = $this->trackEvents($channelId, $requests);

        return $result->mergeWith(
            $this->handleClientTrackingResult($clientResult, $requestOrderIdMap, 'CancelledOrder')
        );
    }


    public function trackPaiedOrders(Context $context, string $channelId, array $orderEvents): OrderTrackingResult
    {
        $requestOrderIdMap = $requests = [];
        $result = new OrderTrackingResult();

        /** @var OrderEventInterface $orderEvent */
        foreach ($orderEvents as $orderEvent) {
            try {
                $request = $this->orderEventRequestTranslator->translateToPaidOrderEventRequest(
                    $context,
                    $orderEvent->getOrder(),
                    $orderEvent->getEventDateTime()
                );
                $requestOrderIdMap[spl_object_id($request)] = $orderEvent->getOrder()->getId();
                $requests[] = $request;
            } catch (\Throwable $e) {
                $result->addFailedOrder($orderEvent->getOrder()->getId(), $e);
            }
        }

        $clientResult = $this->trackEvents($channelId, $requests);

        return $result->mergeWith(
            $this->handleClientTrackingResult($clientResult, $requestOrderIdMap, 'PaidOrder')
        );
    }

    public function trackRefundedOrders(Context $context, string $channelId, array $orderEvents): OrderTrackingResult
    {
        $requestOrderIdMap = $requests = [];
        $result = new OrderTrackingResult();

        /** @var OrderEventInterface $orderEvent */
        foreach ($orderEvents as $orderEvent) {
            try {
                $request = $this->orderEventRequestTranslator->translateToRefundedOrderEventRequest(
                    $context,
                    $orderEvent->getOrder(),
                    $orderEvent->getEventDateTime()
                );
                $requestOrderIdMap[spl_object_id($request)] = $orderEvent->getOrder()->getId();
                $requests[] = $request;
            } catch (\Throwable $e) {
                $result->addFailedOrder($orderEvent->getOrder()->getId(), $e);
            }
        }

        $clientResult = $this->trackEvents($channelId, $requests);

        return $result->mergeWith(
            $this->handleClientTrackingResult($clientResult, $requestOrderIdMap, 'RefundedOrder')
        );
    }

    public function upsertCustomerProfiles(Context $context, string $channelId, CustomerCollection $customers)
    {
        $updateRequests = $createRequests = [];
        $profileIdSearchResult = $this->searchProfileIds($context, $channelId, $customers);

        /** First of all - update existing customer's sensitive fields - id, email, and phone_number  */
        foreach ($profileIdSearchResult->getMapping() as $personId => $customerId) {
            $customer = $customers->get($customerId);
            $updateRequests[] = $this->updateProfileRequestTranslator->translateToProfileRequest($context, $customer, $personId);
        }
        $this->trackEvents($channelId, $updateRequests);

        /** Update/create customer profiles  */
        foreach ($customers as $customer) {
            $createRequests[] = $this->identifyProfileRequestTranslator->translateToProfileRequest($context, $customer);
        }

        return $this->trackEvents($channelId, $createRequests);
    }

    public function searchProfileIds(
        Context $context,
        string $channelId,
        CustomerCollection $customers
    ): ProfileIdSearchResult {
        return $this->profileIdSearchStrategy->searchProfilesIds($context, $channelId, $customers);
    }

    public function trackAddedToCartRequests(string $channelId, array $cartRequests): ClientResult
    {
        return $this->trackEvents($channelId, $cartRequests);
    }

    public function addToKlaviyoProfilesList(
        SalesChannelEntity $salesChannelEntity,
        NewsletterRecipientCollection $recipientCollection,
        string $profilesListId
    ): array {
        try {
            $errors = [];
            $request = $this->subscribersTranslator
                ->translateToAddProfilesRequest($recipientCollection, $profilesListId);
            $clientResult = $this->clientRegistry
                ->getClient($salesChannelEntity->getId())
                ->sendRequests([$request]);

            /** @var AddProfilesToListResponse $result */
            $result = $clientResult->getRequestResponse($request);
            if (!$result->isSuccess()) {
                $error = new \Exception(\sprintf(
                    'Could not add Shopware subscribers to Klaviyo profiles list, reason: %s',
                    $result->getErrorDetails()
                ));
                $errors[] = $error;
                $this->logger->error($error->getMessage());
                $failedEmail = str_replace( ' is not a valid email.', '', $result->getErrorDetails());
                $newCollection = $recipientCollection->filter(fn(Recipient $recipient) => $recipient->getEmail() !== $failedEmail);
                if ($newCollection->count()) {
                    $errors = array_merge(
                        $errors,
                        $this->addToKlaviyoProfilesList($salesChannelEntity, $newCollection, $profilesListId)
                    );
                }
            }

            return $errors;
        } catch (\Throwable $exception) {
            $this->logger->error(
                \sprintf(
                    'Could not add Shopware subscribers to Klaviyo profiles list, reason: %s',
                    $exception->getMessage()
                ),
                ContextHelper::createContextFromException($exception)
            );

            return $errors;
        }
    }

    public function removeKlaviyoSubscribersFromList(
        SalesChannelEntity $salesChannelEntity,
        ProfileContactInfoCollection $profileInfoCollection,
        string $profilesListId
    ): bool {
        try {
            $request = new RemoveProfilesFromListRequest($profilesListId, $profileInfoCollection);
            $clientResult = $this->clientRegistry
                ->getClient($salesChannelEntity->getId())
                ->sendRequests([$request]);

            /** @var RemoveProfilesFromListResponse $result */
            $result = $clientResult->getRequestResponse($request);
            if (!$result->isSuccess()) {
                $this->logger->error(
                    \sprintf(
                        'Could not remove Shopware subscribers from Klaviyo profiles list, reason: %s',
                        $result->getErrorDetails()
                    )
                );
            }

            return $result->isSuccess();
        } catch (\Throwable $exception) {
            $this->logger->error(
                \sprintf(
                    'Could not remove Shopware subscribers from Klaviyo profiles list, reason: %s',
                    $exception->getMessage()
                ),
                ContextHelper::createContextFromException($exception)
            );

            return false;
        }
    }

    public function trackStartedCheckoutRequests(string $channelId, array $checkoutRequests): ClientResult
    {
        return $this->trackEvents($channelId, $checkoutRequests);
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
                    \sprintf(
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

    /**
     * @param string $channelId
     * @param int $count
     * @param int $page
     *
     * @return GetExcludedSubscribers\Response
     * @throws \Exception
     */
    public function getExcludedSubscribersFromList(
        string $channelId,
        int $count,
        int $page
    ): GetExcludedSubscribers\Response {
        $request = new GetExcludedSubscribers\Request($count, $page);
        $clientResult = $this->clientRegistry
            ->getClient($channelId)
            ->sendRequests([$request]);

        /** @var GetExcludedSubscribers\Response $result */
        $result = $clientResult->getRequestResponse($request);
        if (!$result) {
            throw new ProfilesListNotFoundException('Could not get excluded subscribers from Klaviyo.');
        }

        return $result;
    }
}
