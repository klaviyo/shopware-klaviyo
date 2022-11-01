<?php

declare(strict_types=1);

namespace Klaviyo\Integration\EventListener;

use Klaviyo\Integration\Entity\CheckoutMapping\CheckoutMappingDefinition;
use Klaviyo\Integration\Utils\Logger\ContextHelper;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Order\CartConvertedEvent;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Throwable;


class CartEventListeners implements EventSubscriberInterface
{

    private EntityRepositoryInterface $mappingRepository;
    private LoggerInterface $logger;

    public function __construct(
        EntityRepositoryInterface $mappingRepository,
        LoggerInterface $logger
    ) {
        $this->mappingRepository = $mappingRepository;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents()
    {
        return [
            CartConvertedEvent::class => 'onCartConverted',
        ];
    }

    public function onCartConverted(CartConvertedEvent $event)
    {
        try {
            $token = $event->getCart()->getToken();
            $criteria = new Criteria();
            $criteria->addFilter(
                new EqualsFilter('reference', $token),
                new EqualsFilter('mappingTable', CheckoutMappingDefinition::CART_TABLE)
            );
            $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));
            $mapping = $this->mappingRepository->search($criteria, $event->getContext())->first();

            if ($mapping) {
                $this->mappingRepository->upsert(
                    [
                        [
                            'id' => $mapping->getId(),
                            'reference' => $event->getConvertedCart()['id'],
                            'mappingTable' => OrderDefinition::ENTITY_NAME,
                        ],
                    ],
                    $event->getContext()
                );
            }
        } catch (Throwable $throwable) {
            $this->logger->error(
                'Unable to convert od checkout mapping',
                ContextHelper::createContextFromException($throwable)
            );
        }
    }
}
