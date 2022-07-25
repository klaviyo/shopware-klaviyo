<?php declare(strict_types=1);

namespace Klaviyo\Integration\Model\Channel;

use Klaviyo\Integration\Configuration\ConfigurationRegistry;
use Klaviyo\Integration\Exception\InvalidConfigurationException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class GetValidChannels
{
    private ConfigurationRegistry $configurationRegistry;
    private EntityRepositoryInterface $salesChannelRepository;

    public function __construct(
        ConfigurationRegistry $configurationRegistry,
        EntityRepositoryInterface $salesChannelRepository
    ) {
        $this->configurationRegistry = $configurationRegistry;
        $this->salesChannelRepository = $salesChannelRepository;
    }

    public function execute(): EntityCollection
    {
        $criteria = new Criteria();
        $validChannelIds = [];
        $channels = $this->salesChannelRepository->search($criteria, Context::createDefaultContext());

        /** @var SalesChannelEntity $channel */
        foreach ($channels as $channel) {
            try {
                $configuration = $this->configurationRegistry->getConfiguration($channel->getId());
                if ($configuration->isAccountEnabled()) {
                    $validChannelIds[$channel->getId()] = true;
                }
            } catch (InvalidConfigurationException $e) {
                continue;
            }
        }

        return $channels->filter(function(SalesChannelEntity $channel) use ($validChannelIds) {
            return isset($validChannelIds[$channel->getId()]);
        })->getEntities();
    }
}
