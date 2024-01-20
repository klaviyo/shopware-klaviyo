<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Model\Channel;

use Klaviyo\Integration\Exception\InvalidConfigurationException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class GetValidChannels
{
    private GetValidChannelConfig $getValidChannelConfig;
    private EntityRepository $salesChannelRepository;

    public function __construct(
        GetValidChannelConfig $getValidChannelConfig,
        EntityRepository $salesChannelRepository
    ) {
        $this->getValidChannelConfig = $getValidChannelConfig;
        $this->salesChannelRepository = $salesChannelRepository;
    }

    public function execute(Context $context): EntityCollection
    {
        $criteria = new Criteria();
        $validChannelIds = [];
        $channels = $this->salesChannelRepository->search($criteria, $context);

        /** @var SalesChannelEntity $channel */
        foreach ($channels as $channel) {
            try {
                if ($this->getValidChannelConfig->execute($channel->getId())) {
                    $validChannelIds[$channel->getId()] = true;
                }
            } catch (InvalidConfigurationException $e) {
                continue;
            }
        }

        return $channels->filter(function (SalesChannelEntity $channel) use ($validChannelIds) {
            return isset($validChannelIds[$channel->getId()]);
        })->getEntities();
    }
}
