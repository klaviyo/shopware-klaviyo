<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Utils\Lifecycle\Update;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class UpdateTo105
{
    private const CREDENTIALS_CONFIGS = [
        'klavi_overd.config.privateApiKey',
        'klavi_overd.config.publicApiKey',
        'klavi_overd.config.klaviyoListForSubscribersSync',
    ];

    private SystemConfigService $systemConfigService;
    private EntityRepository $salesChannelRepository;

    public function __construct(
        SystemConfigService $systemConfigService,
        EntityRepository $salesChannelRepository
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->salesChannelRepository = $salesChannelRepository;
    }

    public function execute(Context $context): void
    {
        $channelCriteria = new Criteria();
        $channelCriteria->addFilter(
            new EqualsAnyFilter('typeId', [Defaults::SALES_CHANNEL_TYPE_STOREFRONT, Defaults::SALES_CHANNEL_TYPE_API])
        );
        $channelIds = $this->salesChannelRepository->searchIds($channelCriteria, $context);

        /** @var string $channelId */
        foreach ($channelIds->getIds() as $channelId) {
            $active = true;
            foreach (self::CREDENTIALS_CONFIGS as $configName) {
                $config = $this->systemConfigService->getString($configName, $channelId);
                if (empty(trim($config))) {
                    $config = $this->systemConfigService->getString($configName);
                }

                if (empty(trim($config))) {
                    $this->systemConfigService->delete($configName, $channelId);
                    // if one of the configs is empty should be disabled
                    $active = false;
                } else {
                    $this->systemConfigService->set($configName, $config, $channelId);
                }
            }
            $this->systemConfigService->set('klavi_overd.config.enabled', $active, $channelId);
        }

        foreach (self::CREDENTIALS_CONFIGS as $configName) {
            $this->systemConfigService->delete($configName);
        }
    }
}
