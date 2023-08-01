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
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class RestorerService implements RestorerServiceInterface
{
    private EntityRepositoryInterface $mappingRepository;
    private EntityRepositoryInterface $orderRepository;
    private CartRuleLoader $cartRuleLoader;
    private CartService $cartService;
    private OrderConverter $orderConverter;
    private LoggerInterface $logger;

    public function __construct(
        EntityRepositoryInterface $mappingRepository,
        EntityRepositoryInterface $orderRepository,
        CartRuleLoader $cartRuleLoader,
        CartService $cartService,
        OrderConverter $orderConverter,
        LoggerInterface $logger
    ) {
        $this->mappingRepository = $mappingRepository;
        $this->orderRepository = $orderRepository;
        $this->cartRuleLoader = $cartRuleLoader;
        $this->cartService = $cartService;
        $this->orderConverter = $orderConverter;
        $this->logger = $logger;
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
}
