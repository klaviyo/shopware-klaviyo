<?php declare(strict_types=1);

namespace Klaviyo\Integration\Model\Channel;

use Klaviyo\Integration\Configuration\ConfigurationRegistry;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

// TODO: we'll use it future to decrease amount of useless queries to the database.
class ChannelRepositoryWithUniqueLists
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

    public function get(): EntitySearchResult
    {
        $criteria = new Criteria();
        $channels = $this->salesChannelRepository->search($criteria, Context::createDefaultContext());
        $id2listNameMapping = [];

        /** @var SalesChannelEntity $channel */
        foreach ($channels as $channel) {
            $config = $this->configurationRegistry->getConfiguration($channel->getId());

            if (!in_array($config->getSubscribersListName(), $id2listNameMapping)) {
                $id2listNameMapping[$channel->getId()] = $config->getSubscribersListName();
            }
        }

        return $channels->filter(fn(SalesChannelEntity $channel) => isset($id2listNameMapping[$channel->getId()]));
    }
}
