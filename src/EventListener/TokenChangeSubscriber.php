<?php

declare(strict_types=1);

namespace Klaviyo\Integration\EventListener;

use Klaviyo\Integration\Entity\CheckoutMapping\CheckoutMappingDefinition;
use Klaviyo\Integration\Utils\Logger\ContextHelper;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\Event\SalesChannelContextTokenChangeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TokenChangeSubscriber implements EventSubscriberInterface
{
    private EntityRepository $mappingRepository;
    private LoggerInterface $logger;

    public function __construct(
        EntityRepository $mappingRepository,
        LoggerInterface $logger
    ) {
        $this->mappingRepository = $mappingRepository;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SalesChannelContextTokenChangeEvent::class => 'onTokenChange',
        ];
    }

    public function onTokenChange(SalesChannelContextTokenChangeEvent $event): void
    {
        try {
            $oldToken = $event->getPreviousToken();
            $newToken = $event->getCurrentToken();

            // Find all cart mappings with the old token
            $criteria = new Criteria();
            $criteria->addFilter(
                new EqualsFilter('reference', $oldToken),
                new EqualsFilter('mappingTable', CheckoutMappingDefinition::CART_TABLE)
            );

            $mappings = $this->mappingRepository->search($criteria, $event->getContext())->getElements();

            // Update each mapping with the new token
            if (!empty($mappings)) {
                $updates = [];
                foreach ($mappings as $mapping) {
                    $updates[] = [
                        'id' => $mapping->getId(),
                        'reference' => $newToken,
                    ];
                }

                $this->mappingRepository->update($updates, $event->getContext());
            }
        } catch (\Throwable $throwable) {
            $this->logger->error(
                'Unable to update Klaviyo checkout mapping token',
                ContextHelper::createContextFromException($throwable)
            );
        }
    }
}
