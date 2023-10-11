<?php

namespace Klaviyo\Integration\Storefront\Checkout\Cart\RestorerService;

use Klaviyo\Integration\Entity\CheckoutMapping\CheckoutMappingDefinition;
use Klaviyo\Integration\Entity\CheckoutMapping\CheckoutMappingEntity;
use Klaviyo\Integration\Utils\Logger\ContextHelper;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartRuleLoader;
use Shopware\Core\Checkout\Cart\Order\OrderConverter;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Throwable;

class RestorerService implements RestorerServiceInterface
{
    private EntityRepository $mappingRepository;
    private EntityRepository $orderRepository;
    private CartRuleLoader $cartRuleLoader;
    private CartService $cartService;
    private OrderConverter $orderConverter;
    private LoggerInterface $logger;
    private EntityRepository $customerRepository;

    public function __construct(
        EntityRepository $mappingRepository,
        EntityRepository $orderRepository,
        CartRuleLoader $cartRuleLoader,
        CartService $cartService,
        OrderConverter $orderConverter,
        LoggerInterface $logger,
        EntityRepository $customerRepository
    ) {
        $this->mappingRepository = $mappingRepository;
        $this->orderRepository = $orderRepository;
        $this->cartRuleLoader = $cartRuleLoader;
        $this->cartService = $cartService;
        $this->orderConverter = $orderConverter;
        $this->logger = $logger;
        $this->customerRepository = $customerRepository;
    }

    public function restore(string $mappingId, SalesChannelContext $context): void
    {
        try {
            $mapping = $this->loadMapping($mappingId, $context->getContext());
            if ($mapping == null) {
                return;
            }

            $mapping->getMappingTable() === CheckoutMappingDefinition::CART_TABLE ? $this->restoreCart(
                $mapping->getReference(),
                $context
            ) : $this->restoreOrder($mapping->getReference(), $context);
        } catch (Throwable $throwable) {
            $this->logger->error(
                'Unable to restore the cart',
                ContextHelper::createContextFromException($throwable)
            );
        }
    }

    public function registerCustomerByRestoreCartLink(SalesChannelContext $context): RequestDataBag
    {
        $data = new RequestDataBag();

        if (!isset($context->customerObject)) {
            return $data;
        }

        $customer = $context->customerObject;

        $customerShippingAddress = $customer->getDefaultShippingAddress();
        $customerBillingAddress = $customer->getDefaultBillingAddress();

        $data->set('salutationId', $customerBillingAddress->getSalutationId());
        $data->set('firstName', $customerBillingAddress->getFirstName());
        $data->set('lastName', $customerBillingAddress->getLastName());
        $data->set('email', $customer->getEmail());

        $data->set('redirectTo', "frontend.checkout.confirm.page");
        $data->set('redirectParameters', "");
        $data->set('errorRoute', "frontend.checkout.register.page");
        $data->set('accountType', "");
        $data->set('shopware_surname_confirm', "");
        $data->set('guest', true);

        $billingData = $this->preparingAddressData($customerBillingAddress);
        $shippingData = $this->preparingAddressData($customerShippingAddress);

        $data->set('shippingAddress', $shippingData);
        $data->set('billingAddress', $billingData);

        return $data;
    }

    protected function loadMapping(string $mappingId, Context $context): ?CheckoutMappingEntity
    {
        return $this->mappingRepository->search(new Criteria([$mappingId]), $context)->first();
    }

    protected function restoreCart(string $token, SalesChannelContext $context): void
    {
        $cart = $this->cartRuleLoader->loadByToken($context, $token)->getCart();
        $this->restoreByCart($cart, $context);
    }

    protected function restoreByCart(Cart $cart, SalesChannelContext $context): void
    {
        $result = [];
        foreach ($cart->getLineItems() as $lineItem) {
            $result[] = $lineItem;
        }
        if (!empty($result)) {
            $currentCart = $this->cartRuleLoader->loadByToken($context, $context->getToken())->getCart();
            foreach ($currentCart->getLineItems() as $lineItem) {
                $this->cartService->remove($currentCart, $lineItem->getId(), $context);
            }
            $this->cartService->add($currentCart, $result, $context);
        }
    }

    protected function restoreOrder(string $orderId, SalesChannelContext $context): void
    {
        $order = $this->getOrderById($orderId, $context->getContext());
        if ($order == null) {
            return;
        }

        if ($customerId = $order->getOrderCustomer()->getCustomer()->getId()) {
            $context->assign(['customerId' => $customerId]);

            $criteria = new Criteria();
            $criteria->addAssociation('addresses');
            $criteria->addAssociation('defaultBillingAddress');
            $criteria->addAssociation('defaultShippingAddress');
            $criteria->addFilter(new EqualsFilter('id', $customerId));

            /** @var CustomerEntity|null $customer */
            $customer = $this->customerRepository->search($criteria, $context->getContext())->first();

            if ($customer !== null) {
                $context->assign(['customerObject' => $customer]);
            }
        }

        $cart = $this->orderConverter->convertToCart($order, $context->getContext());
        $this->restoreByCart($cart, $context);
    }

    private function getOrderById(string $orderId, Context $context): ?OrderEntity
    {
        $criteria = (new Criteria([$orderId]))
            ->addAssociation('lineItems')
            ->addAssociation('currency')
            ->addAssociation('deliveries')
            ->addAssociation('language.locale')
            ->addAssociation('orderCustomer.customer')
            ->addAssociation('billingAddress')
            ->addAssociation('transactions');

        return $this->orderRepository->search($criteria, $context)
            ->get($orderId);
    }

    private function preparingAddressData(CustomerAddressEntity $addressData): RequestDataBag
    {
        $resultData = new RequestDataBag();

        foreach($addressData->getVars() as $key => $value) {
            if (in_array($key, ['customerId', '_uniqueIdentifier'])) {
                continue;
            }

            if (!is_object($value) && !is_array($value)) {
                $resultData->set($key, $value);
            }
        }

        return $resultData;
    }
}
