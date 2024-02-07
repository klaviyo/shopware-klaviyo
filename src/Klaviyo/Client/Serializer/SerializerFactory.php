<?php

namespace Klaviyo\Integration\Klaviyo\Client\Serializer;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\CanceledOrderEventTrackingRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\FulfilledOrderEventTrackingRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\PaidOrderEventTrackingRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\PlacedOrderEventTrackingRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\RefundedOrderEventTrackingRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\ShippedOrderEventTrackingRequest;
use Klaviyo\Integration\Klaviyo\Client\Configuration\ConfigurationInterface;
use Klaviyo\Integration\Klaviyo\Client\Serializer\Denormalizer\AddProfilesToListResponseDenormalizer;
use Klaviyo\Integration\Klaviyo\Client\Serializer\Denormalizer\CollectionDenormalizer;
use Klaviyo\Integration\Klaviyo\Client\Serializer\Denormalizer\GetAccountDenormalizer;
use Klaviyo\Integration\Klaviyo\Client\Serializer\Denormalizer\GetExcludedSubscribersResponseDenormalizer;
use Klaviyo\Integration\Klaviyo\Client\Serializer\Denormalizer\GetListProfilesResponseDenormalizer;
use Klaviyo\Integration\Klaviyo\Client\Serializer\Denormalizer\GetProfileIdResponseDenormalizer;
use Klaviyo\Integration\Klaviyo\Client\Serializer\Denormalizer\GetProfilesListsResponseDenormalizer;
use Klaviyo\Integration\Klaviyo\Client\Serializer\Denormalizer\ProfileInfoDenormalizer;
use Klaviyo\Integration\Klaviyo\Client\Serializer\Denormalizer\RemoveProfilesFromListResponseDenormalizer;
use Klaviyo\Integration\Klaviyo\Client\Serializer\Denormalizer\SubscribeToListResponseDenormalizer;
use Klaviyo\Integration\Klaviyo\Client\Serializer\Denormalizer\UpdateProfileResponseDenormalizer;
use Klaviyo\Integration\Klaviyo\Client\Serializer\Normalizer\AddedToCartEventTrackingRequestNormalizer;
use Klaviyo\Integration\Klaviyo\Client\Serializer\Normalizer\AddProfilesToListRequestsNormalizer;
use Klaviyo\Integration\Klaviyo\Client\Serializer\Normalizer\AddressNormalizer;
use Klaviyo\Integration\Klaviyo\Client\Serializer\Normalizer\ConfigurableOrderEventTrackingRequestNormalizer;
use Klaviyo\Integration\Klaviyo\Client\Serializer\Normalizer\CustomerPropertiesNormalizer;
use Klaviyo\Integration\Klaviyo\Client\Serializer\Normalizer\IdentifyProfileRequestNormalizer;
use Klaviyo\Integration\Klaviyo\Client\Serializer\Normalizer\OrderedProductEventTrackingRequestNormalizer;
use Klaviyo\Integration\Klaviyo\Client\Serializer\Normalizer\RealSubscribersToKlaviyoRequestNormalizer;
use Klaviyo\Integration\Klaviyo\Client\Serializer\Normalizer\RemoveProfilesFromListRequestNormalizer;
use Klaviyo\Integration\Klaviyo\Client\Serializer\Normalizer\StartedCheckoutEventTrackingRequestNormalizer;
use Klaviyo\Integration\Klaviyo\Client\Serializer\Normalizer\SubscribeToListRequestNormalizer;
use Klaviyo\Integration\Klaviyo\Client\Serializer\Normalizer\UpdateProfileRequestNormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class SerializerFactory
{
    public function create(ConfigurationInterface $configuration): SerializerInterface
    {
        return new Serializer(
            [
                new CustomerPropertiesNormalizer(),
                new AddressNormalizer(),
                new OrderedProductEventTrackingRequestNormalizer($configuration),
                new ConfigurableOrderEventTrackingRequestNormalizer(
                    $configuration,
                    PlacedOrderEventTrackingRequest::class,
                    'Placed Order'
                ),
                new ConfigurableOrderEventTrackingRequestNormalizer(
                    $configuration,
                    FulfilledOrderEventTrackingRequest::class,
                    'Fulfilled Order'
                ),
                new ConfigurableOrderEventTrackingRequestNormalizer(
                    $configuration,
                    CanceledOrderEventTrackingRequest::class,
                    'Cancelled Order'
                ),
                new ConfigurableOrderEventTrackingRequestNormalizer(
                    $configuration,
                    RefundedOrderEventTrackingRequest::class,
                    'Refunded Order'
                ),
                new ConfigurableOrderEventTrackingRequestNormalizer(
                    $configuration,
                    PaidOrderEventTrackingRequest::class,
                    'Paid Order'
                ),
                new ConfigurableOrderEventTrackingRequestNormalizer(
                    $configuration,
                    ShippedOrderEventTrackingRequest::class,
                    'Shipped Order'
                ),
                new AddedToCartEventTrackingRequestNormalizer($configuration),
                new AddProfilesToListRequestsNormalizer($configuration),
                new SubscribeToListRequestNormalizer($configuration),
                new RealSubscribersToKlaviyoRequestNormalizer($configuration),
                new RemoveProfilesFromListRequestNormalizer($configuration),
                new UpdateProfileRequestNormalizer($configuration),
                new AddProfilesToListResponseDenormalizer(),
                new SubscribeToListResponseDenormalizer(),
                new GetProfilesListsResponseDenormalizer(),
                new ProfileInfoDenormalizer(),
                new GetListProfilesResponseDenormalizer(),
                new CollectionDenormalizer(),
                new RemoveProfilesFromListResponseDenormalizer(),
                new IdentifyProfileRequestNormalizer($configuration),
                new GetExcludedSubscribersResponseDenormalizer(),
                new GetProfileIdResponseDenormalizer(),
                new UpdateProfileResponseDenormalizer(),
                new StartedCheckoutEventTrackingRequestNormalizer($configuration),
                new GetAccountDenormalizer(),
            ],
            [
                new JsonEncoder(),
            ]
        );
    }
}
