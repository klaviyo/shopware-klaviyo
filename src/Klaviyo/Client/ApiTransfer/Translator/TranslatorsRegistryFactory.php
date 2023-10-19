<?php

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Translator;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\CartEvent\AddedToCartEventTrackingRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderedProductEvent\OrderedProductEventTrackingRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\CanceledOrderEventTrackingRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\FulfilledOrderEventTrackingRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\PaidOrderEventTrackingRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\ShippedOrderEventTrackingRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\PlacedOrderEventTrackingRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\RefundedOrderEventTrackingRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Translator\Profiles\Search\SearchProfileIdApiTransferTranslator;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Translator\Profiles\Update\UpdateProdileApiTransferTranslator;
use Klaviyo\Integration\Klaviyo\Client\Configuration\ConfigurationInterface;
use Klaviyo\Integration\Klaviyo\Client\Serializer\SerializerFactory;
use Klaviyo\Integration\Klaviyo\FrontendApi\DTO\StartedCheckoutEventTrackingRequest;

class TranslatorsRegistryFactory
{
    private SerializerFactory $serializerFactory;

    public function __construct(SerializerFactory $serializerFactory)
    {
        $this->serializerFactory = $serializerFactory;
    }

    public function create(ConfigurationInterface $configuration): TranslatorsRegistry
    {
        $registry = new TranslatorsRegistry();

        $serializer = $this->serializerFactory->create($configuration);

        $registry->addTranslator(
            new GenericEventTrackingApiTransferTranslator($serializer, $configuration, OrderedProductEventTrackingRequest::class)
        );

        $registry->addTranslator(
            new GenericEventTrackingApiTransferTranslator($serializer, $configuration, PlacedOrderEventTrackingRequest::class)
        );

        $registry->addTranslator(
            new GenericEventTrackingApiTransferTranslator($serializer, $configuration, FulfilledOrderEventTrackingRequest::class)
        );

        $registry->addTranslator(
            new GenericEventTrackingApiTransferTranslator($serializer, $configuration, CanceledOrderEventTrackingRequest::class)
        );

        $registry->addTranslator(
            new GenericEventTrackingApiTransferTranslator($serializer, $configuration, RefundedOrderEventTrackingRequest::class)
        );
        $registry->addTranslator(
            new GenericEventTrackingApiTransferTranslator($serializer, $configuration, PaidOrderEventTrackingRequest::class)
        );
        $registry->addTranslator(
            new GenericEventTrackingApiTransferTranslator($serializer, $configuration, ShippedOrderEventTrackingRequest::class)
        );
        $registry->addTranslator(
            new GenericEventTrackingApiTransferTranslator($serializer, $configuration, AddedToCartEventTrackingRequest::class)
        );
        $registry->addTranslator(
            new GenericEventTrackingApiTransferTranslator($serializer, $configuration, StartedCheckoutEventTrackingRequest::class)
        );
        $registry->addTranslator(
            new AddProfilesToListApiTransferTranslator($serializer, $configuration)
        );
        $registry->addTranslator(
            new RemoveProfilesFromListApiTransferTranslator($serializer, $configuration)
        );
        $registry->addTranslator(
            new GetProfilesListsApiTransferTranslator($serializer, $configuration)
        );
        $registry->addTranslator(
            new GetListProfilesRequestApiTransferTranslator($serializer, $configuration)
        );
        $registry->addTranslator(
            new IdentifyProfileRequestApiTransferTranslator($serializer, $configuration)
        );
        $registry->addTranslator(
            new GetExcludedSubscribersApiTransferTranslator($serializer, $configuration)
        );
        $registry->addTranslator(
            new SearchProfileIdApiTransferTranslator($serializer, $configuration)
        );
        $registry->addTranslator(
            new UpdateProdileApiTransferTranslator($serializer, $configuration)
        );
        $registry->addTranslator(
            new GetAccountApiTransferTranslator($serializer, $configuration)
        );

        return $registry;
    }
}
