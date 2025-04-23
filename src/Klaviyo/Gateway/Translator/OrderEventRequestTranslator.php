<?php

namespace Klaviyo\Integration\Klaviyo\Gateway\Translator;

use Klaviyo\Integration\Configuration\ConfigurationRegistry;
use Klaviyo\Integration\Entity\Helper\AddressDataHelper;
use Klaviyo\Integration\Entity\Helper\ProductDataHelper;
use Klaviyo\Integration\Exception\JobRuntimeWarningException;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\Common\Address;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\AbstractOrderEventTrackingRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\CanceledOrderEventTrackingRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\DTO\DiscountInfo;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\DTO\DiscountInfoCollection;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\DTO\OrderProductItemInfo;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\DTO\OrderProductItemInfoCollection;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\FulfilledOrderEventTrackingRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\PaidOrderEventTrackingRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\ShippedOrderEventTrackingRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\PlacedOrderEventTrackingRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\RefundedOrderEventTrackingRequest;
use Klaviyo\Integration\Klaviyo\Client\Exception\OrderItemProductNotFound;
use Klaviyo\Integration\Klaviyo\Gateway\Exception\TranslationException;
use Klaviyo\Integration\Utils\Reflection\ReflectionHelper;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

class OrderEventRequestTranslator
{
    private const ORDER_CANCELLED_REASON = 'Cancelled by shopware 6';
    private const ORDER_REFUND_REASON = 'Refund by shopware 6';
    private const ORDER_PAID_REASON = 'Paid by shopware 6';

    private EntityRepositoryInterface $orderAddressRepository;
    private EntityRepositoryInterface $orderDeliveryRepository;
    private EntityRepositoryInterface $orderLineItemRepository;
    private AddressDataHelper $addressDataHelper;
    private CustomerPropertiesTranslator $orderCustomerPropertiesTranslator;
    private ProductDataHelper $productDataHelper;
    private ConfigurationRegistry $configurationRegistry;

    public function __construct(
        EntityRepositoryInterface $orderAddressRepository,
        EntityRepositoryInterface $orderDeliveryRepository,
        EntityRepositoryInterface $orderLineItemRepository,
        AddressDataHelper $addressDataHelper,
        ProductDataHelper $productDataHelper,
        CustomerPropertiesTranslator $orderCustomerPropertiesTranslator,
        ConfigurationRegistry $configurationRegistry
    ) {
        $this->orderAddressRepository = $orderAddressRepository;
        $this->orderDeliveryRepository = $orderDeliveryRepository;
        $this->orderLineItemRepository = $orderLineItemRepository;
        $this->addressDataHelper = $addressDataHelper;
        $this->productDataHelper = $productDataHelper;
        $this->orderCustomerPropertiesTranslator = $orderCustomerPropertiesTranslator;
        $this->configurationRegistry = $configurationRegistry;
    }

    /**
     * @throws JobRuntimeWarningException
     */
    public function translateToPlacedOrderEventRequest(
        Context $context,
        OrderEntity $orderEntity
    ): PlacedOrderEventTrackingRequest {
        $orderTransaction = $orderEntity->getTransactions() ? $orderEntity->getTransactions()->first() : null;
        $actualOrderTime = $orderTransaction ? $orderTransaction->getCreatedAt() : $orderEntity->getCreatedAt();

        /** @var PlacedOrderEventTrackingRequest $result */
        $result = $this->translateToOrderEventTrackingRequest(
            $context,
            PlacedOrderEventTrackingRequest::class,
            $orderEntity,
            $actualOrderTime
        );

        return $result;
    }

    private function translateToDiscountInfoCollection(
        Context $context,
        OrderEntity $orderEntity
    ): DiscountInfoCollection {
        $discounts = new DiscountInfoCollection();
        $this->ensureOrderLineItemsLoaded($context, $orderEntity);
        /** @var OrderLineItemEntity $lineItem */
        foreach ($orderEntity->getLineItems() ?? [] as $lineItem) {
            if (LineItem::PROMOTION_LINE_ITEM_TYPE === $lineItem->getType()) {
                $discounts->add(new DiscountInfo($lineItem->getLabel(), $lineItem->getTotalPrice()));
            }
        }

        return $discounts;
    }

    private function translateOrderAddress(Context $context, ?OrderAddressEntity $orderAddressEntity): ?Address
    {
        if (!$orderAddressEntity) {
            return null;
        }

        $state = $this->addressDataHelper->getAddressRegion($context, $orderAddressEntity);
        $country = $this->addressDataHelper->getAddressCountry($context, $orderAddressEntity);

        return new Address(
            $orderAddressEntity->getFirstName(),
            $orderAddressEntity->getLastName(),
            $orderAddressEntity->getCompany(),
            $orderAddressEntity->getStreet(),
            $orderAddressEntity->getAdditionalAddressLine1(),
            $orderAddressEntity->getCity(),
            $state ? $state->getName() : null,
            $state ? $state->getShortCode() : null,
            $country ? $country->getName() : null,
            $country ? $country->getIso() : null,
            $orderAddressEntity->getZipcode(),
            $orderAddressEntity->getPhoneNumber()
        );
    }

    private function getOrderBillingAddress(Context $context, OrderEntity $orderEntity): OrderAddressEntity
    {
        if ($orderEntity->getBillingAddress()) {
            return $orderEntity->getBillingAddress();
        }

        return $this->getOrderAddressById($context, $orderEntity->getBillingAddressId());
    }

    private function getOrderShippingAddress(Context $context, OrderEntity $orderEntity): ?OrderAddressEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderId', $orderEntity->getId()));
        $criteria->addSorting(new FieldSorting('createdAt', 'DESC'));
        $criteria->setLimit(1);

        /** @var OrderDeliveryEntity|null $delivery */
        $delivery = $this->orderDeliveryRepository
            ->search($criteria, $context)
            ->first();

        if ($delivery) {
            return $delivery->getShippingOrderAddress();
        }

        return null;
    }

    private function getOrderAddressById(Context $context, string $id): OrderAddressEntity
    {
        $address = $this->orderAddressRepository->search(new Criteria([$id]), $context)->first();
        if (!$address) {
            throw new TranslationException(
                \sprintf('Address[id: %s] was not found', $id)
            );
        }

        return $address;
    }

    /**
     * @throws JobRuntimeWarningException
     */
    public function translateToCanceledOrderEventRequest(
        Context $context,
        OrderEntity $orderEntity,
        \DateTimeInterface $eventHappenedDateTime
    ): CanceledOrderEventTrackingRequest {
        /** @var CanceledOrderEventTrackingRequest $result */
        $result = $this->translateToOrderEventTrackingRequest(
            $context,
            CanceledOrderEventTrackingRequest::class,
            $orderEntity,
            $eventHappenedDateTime,
            self::ORDER_CANCELLED_REASON
        );

        return $result;
    }

    /**
     * @throws JobRuntimeWarningException
     */
    public function translateToPaidOrderEventRequest(
        Context $context,
        OrderEntity $orderEntity,
        \DateTimeInterface $eventHappenedDateTime
    ): PaidOrderEventTrackingRequest {
        /** @var PaidOrderEventTrackingRequest $result */
        $result = $this->translateToOrderEventTrackingRequest(
            $context,
            PaidOrderEventTrackingRequest::class,
            $orderEntity,
            $eventHappenedDateTime,
            self::ORDER_PAID_REASON
        );

        return $result;
    }

    /**
     * @throws JobRuntimeWarningException
     */
    public function translateToRefundedOrderEventRequest(
        Context $context,
        OrderEntity $orderEntity,
        \DateTimeInterface $eventHappenedDateTime
    ): RefundedOrderEventTrackingRequest {
        /** @var RefundedOrderEventTrackingRequest $result */
        $result = $this->translateToOrderEventTrackingRequest(
            $context,
            RefundedOrderEventTrackingRequest::class,
            $orderEntity,
            $eventHappenedDateTime,
            self::ORDER_REFUND_REASON
        );

        return $result;
    }

    /**
     * @throws JobRuntimeWarningException
     */
    public function translateToFulfilledOrderEventRequest(
        Context $context,
        OrderEntity $orderEntity,
        \DateTimeInterface $eventHappenedDateTime
    ): FulfilledOrderEventTrackingRequest {
        /** @var FulfilledOrderEventTrackingRequest $result */
        $result = $this->translateToOrderEventTrackingRequest(
            $context,
            FulfilledOrderEventTrackingRequest::class,
            $orderEntity,
            $eventHappenedDateTime
        );

        return $result;
    }

    /**
     * @throws JobRuntimeWarningException
     */
    public function translateToShippedOrderEventRequest(
        Context $context,
        OrderEntity $orderEntity,
        \DateTimeInterface $eventHappenedDateTime
    ): ShippedOrderEventTrackingRequest {
        /** @var ShippedOrderEventTrackingRequest $result */
        $result = $this->translateToOrderEventTrackingRequest(
            $context,
            ShippedOrderEventTrackingRequest::class,
            $orderEntity,
            $eventHappenedDateTime
        );

        return $result;
    }

    /**
     * @throws JobRuntimeWarningException
     */
    private function translateToOrderEventTrackingRequest(
        Context $context,
        string $className,
        OrderEntity $orderEntity,
        \DateTimeInterface $eventHappenedDateTime,
        ?string $reasonMessage = null
    ): AbstractOrderEventTrackingRequest {
        if (!ReflectionHelper::isClassInstanceOf($className, AbstractOrderEventTrackingRequest::class)) {
            throw new TranslationException(
                \sprintf(
                    'Unexpected Event tracking request class "%s", descendants of %s expected',
                    $className,
                    AbstractOrderEventTrackingRequest::class
                )
            );
        }

        $customerProperties = $this->orderCustomerPropertiesTranslator
            ->translateOrder($context, $orderEntity);

        $discounts = $this->translateToDiscountInfoCollection($context, $orderEntity);
        $products = $this->translateToOrderInfoCollection($context, $orderEntity);

        $billingAddressEntity = $this->getOrderBillingAddress($context, $orderEntity);
        $billingAddress = $this->translateOrderAddress($context, $billingAddressEntity);

        $shippingAddressEntity = $this->getOrderShippingAddress($context, $orderEntity);
        if ($shippingAddressEntity) {
            $shippingAddress = $this->translateOrderAddress($context, $shippingAddressEntity);
        } else {
            $shippingAddress = $billingAddress;
        }

        $orderCustomFields = [];

        if ($className === PlacedOrderEventTrackingRequest::class) {
            $orderCustomFields = $this->prepareOrderCustomFields($orderEntity);
        }

        /** @var AbstractOrderEventTrackingRequest $request */
        $request = new $className(
            $orderEntity->getId(),
            $eventHappenedDateTime,
            $customerProperties,
            $orderEntity->getAmountTotal(),
            $orderEntity->getShippingCosts()->getTotalPrice(),
            $context->orderIdentificationFlag == 'order-id' ? $orderEntity->getId() : $orderEntity->getOrderNumber(),
            $discounts,
            $products,
            $billingAddress,
            $shippingAddress,
            $reasonMessage,
            $orderCustomFields
        );

        return $request;
    }

    private function translateToOrderInfoCollection(
        Context $context,
        OrderEntity $orderEntity
    ): OrderProductItemInfoCollection {
        $products = new OrderProductItemInfoCollection();

        $this->ensureOrderLineItemsLoaded($context, $orderEntity);

        $customOptions = $this->productDataHelper->getCustomOptionsFromOrderItems(
            $orderEntity->getLineItems() ?? [],
            'OrderedProductEvent'
        );
        if (!empty($customOptions)) {
            $customOptionsData = $customOptions->getCustomOptions();
            $customOptionsProductsData = $customOptions->getCustomOptionsProductsData();
        }

        /** @var OrderLineItemEntity $lineItem */
        foreach ($orderEntity->getLineItems() ?? [] as $lineItem) {
            if ($lineItem->getType() === 'product') {
                try {
                    $parentId = $lineItem->getParentId();
                    $customOption = [];

                    if ($parentId && !empty($customOptionsData[$parentId]) && !empty($customOptionsProductsData[$parentId])) {
                        $customOption =  $customOptionsData[$lineItem->getParentId()];
                        $lineItem->setTotalPrice($customOptionsProductsData[$parentId]);
                    }

                    $product = $this->productDataHelper->getLineItemProduct($context, $lineItem);
                    $productUrl = $this->productDataHelper->getProductViewPageUrlByChannelId(
                        $product,
                        $orderEntity->getSalesChannelId(),
                        $context,
                        $orderEntity->getLanguageId()
                    );
                    $productNumber = $product->getProductNumber();
                    $imageUrl = $this->productDataHelper->getCoverImageUrl($context, $product);
                    $categories = $this->productDataHelper->getCategoryNames($context, $product);
                    $manufacturerName = $this->productDataHelper->getManufacturerName($context, $product) ?? '';
                } catch (OrderItemProductNotFound $e) {
                    // TODO: fix such behavior more elegant in future.
                    $productUrl = $imageUrl = $manufacturerName = '';
                    $productNumber = 'deleted';
                    $categories = [];
                }

                $products->add(
                    new OrderProductItemInfo(
                        $context->productIdentificationType == 'product-id' ? $lineItem->getId() : $productNumber,
                        $productNumber,
                        $lineItem->getLabel(),
                        $lineItem->getQuantity(),
                        $lineItem->getUnitPrice(),
                        $lineItem->getTotalPrice(),
                        $productUrl,
                        $imageUrl,
                        $categories,
                        $manufacturerName,
                        $customOption
                    )
                );
            }
        }

        return $products;
    }

    private function ensureOrderLineItemsLoaded(
        Context $context,
        OrderEntity $orderEntity
    ): void {
        if ($orderEntity->getLineItems() !== null) {
            return;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderId', $orderEntity->getId()));

        $criteria->addAssociation('product');

        /** @var OrderLineItemCollection $collection */
        $collection = $this->orderLineItemRepository->search($criteria, $context)->getEntities();
        $orderEntity->setLineItems($collection);
    }

    private function prepareOrderCustomFields(OrderEntity $order): array
    {
        if (null === $order) {
            return [];
        }

        $channelId = $order->getSalesChannelId();

        $configuration = $this->configurationRegistry->getConfiguration($channelId);
        $fieldMapping = $configuration->getOrderCustomFieldMapping();
        $customFields = [];

        foreach ($order->getCustomFields() ?? [] as $fieldName => $fieldValue) {
            if (isset($fieldMapping[$fieldName]) && $fieldValue) {
                $customFields[$fieldMapping[$fieldName]] = $fieldValue;
            }
        }

        return $customFields;
    }
}
