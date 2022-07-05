<?php declare(strict_types=1);

namespace Klaviyo\Integration\Utils\Lifecycle\Update;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\SystemConfig\SystemConfigEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UpdateTo105
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function execute(Context $context): void
    {
        $configService = $this->container->get(SystemConfigService::class);
        $channelRepository = $this->container->get('sales_channel.repository');
        $globalCredentialsConfig = $this->getPreparedCredentialsConfig($context);

        $channelCriteria = new Criteria();
        $channelCriteria->addFilter(new EqualsAnyFilter(
            'typeId',
            [Defaults::SALES_CHANNEL_TYPE_STOREFRONT, Defaults::SALES_CHANNEL_TYPE_API]
        ));
        $context = Context::createDefaultContext();
        $channels = $channelRepository->search($channelCriteria, $context);

        /** @var SalesChannelEntity $channel */
        foreach ($channels as $channel) {
            $channelCredentialsConfig = $this->getPreparedCredentialsConfig($context, $channel->getId());
            if ($channelCredentialsConfig) {
                // Account was configured on sales-channel level. Just enable it programmatically.
                $configService->set('KlaviyoIntegrationPlugin.config.enabled', true, $channel->getId());
            } else if ($globalCredentialsConfig && !$channelCredentialsConfig) {
                // Account was configured globally and inherited on sales-channel level.
                // Move settings from globals and enable account.
                foreach ($globalCredentialsConfig as $shortKey => $value) {
                    $configService->set('KlaviyoIntegrationPlugin.config.' . $shortKey, $value, $channel->getId());
                }
                $configService->set('KlaviyoIntegrationPlugin.config.enabled', true, $channel->getId());
            }
        }

        // Drop existing global config if it exists.
        if ($globalCredentialsConfig) {
            foreach ($globalCredentialsConfig as $shortKey => $value) {
                $configService->delete('KlaviyoIntegrationPlugin.config.' . $shortKey);
            }
        }
    }

    private function getPreparedCredentialsConfig(Context $context, ?string $channelId = null): ?array
    {
        $configRepository = $this->container->get('system_config.repository');
        $preparedConfig = [];
        $configCriteria = new Criteria();
        $configCriteria->addFilter(new EqualsAnyFilter(
            'configurationKey',
            [
                'KlaviyoIntegrationPlugin.config.privateApiKey',
                'KlaviyoIntegrationPlugin.config.publicApiKey',
                'KlaviyoIntegrationPlugin.config.klaviyoListForSubscribersSync',
            ]
        ));
        $configCriteria->addFilter(new EqualsFilter('salesChannelId', $channelId));
        $configs = $configRepository->search($configCriteria, $context);

        /** @var SystemConfigEntity $configItem */
        foreach ($configs as $configItem) {
            $shortKey = \array_reverse(\explode('.', $configItem->getConfigurationKey()))[0] ?? null;
            if ($shortKey !== null) {
                $preparedConfig[$shortKey] = $configItem->getConfigurationValue();
            }
        }

        if (\count(\array_filter($preparedConfig)) !== 3) {
            // Plugin was not configured properly - some essential credential settings was not set.
            return null;
        }

        return $preparedConfig;
    }
}
