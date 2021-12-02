<?php

namespace Klaviyo\Integration\Tracking;

use Klaviyo\Integration\Klaviyo\CatalogFeed\CatalogFeedConstructor;
use Klaviyo\Integration\Klaviyo\CatalogFeed\CatalogFeedProductItemCollection;
use Klaviyo\Integration\Utils\Logger\ContextHelper;
use Psr\Log\LoggerInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class TrackingCatalogFeedProvider
{
    private CatalogFeedConstructor $catalogFeedConstructor;
    private LoggerInterface $logger;

    public function __construct(CatalogFeedConstructor $catalogFeedConstructor, LoggerInterface $logger)
    {
        $this->catalogFeedConstructor = $catalogFeedConstructor;
        $this->logger = $logger;
    }

    public function getCatalogFeed(SalesChannelContext $context): CatalogFeedProductItemCollection
    {
        try {
            return $this->catalogFeedConstructor
                ->constructCatalogFeed($context->getContext(), $context->getSalesChannel());
        } catch (\Throwable $throwable) {
            $this->logger
                ->error(
                    sprintf('Could not construct catalog feed, reason: %s', $throwable->getMessage()),
                    ContextHelper::createContextFromException($throwable)
                );

            return new CatalogFeedProductItemCollection();
        }
    }
}