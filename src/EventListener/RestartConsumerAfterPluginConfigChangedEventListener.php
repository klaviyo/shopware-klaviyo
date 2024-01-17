<?php

declare(strict_types=1);

namespace Klaviyo\Integration\EventListener;

use Klaviyo\Integration\Klaviyo\Gateway\CachedGetListIdByListName;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Shopware\Core\System\SystemConfig\Event\SystemConfigChangedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\EventListener\StopWorkerOnRestartSignalListener;

class RestartConsumerAfterPluginConfigChangedEventListener implements EventSubscriberInterface
{
    private CacheItemPoolInterface $restartSignalCachePool;
    private CacheItemPoolInterface $cachePool;
    private LoggerInterface $logger;

    private bool $consumersRestarted = false;
    private bool $listCacheCleared = false;

    public function __construct(
        CacheItemPoolInterface $restartSignalCachePool,
        CacheItemPoolInterface $cachePool,
        LoggerInterface $logger
    ) {
        $this->restartSignalCachePool = $restartSignalCachePool;
        $this->cachePool = $cachePool;
        $this->logger = $logger;
    }

    public function onSystemConfigurationChange(SystemConfigChangedEvent $systemConfigChangedEvent): void
    {
        try {
            $key = $systemConfigChangedEvent->getKey();
            if (strpos($key, 'klavi_overd.config') === 0) {
                if ($key === 'klavi_overd.config.klaviyoListForSubscribersSync' && !$this->listCacheCleared) {
                    $this->listCacheCleared = true;
                    $this->cachePool->deleteItem(
                        CachedGetListIdByListName::CACHE_PREFIX . $systemConfigChangedEvent->getSalesChannelId()
                    );
                }

                if (!$this->consumersRestarted) {
                    $this->consumersRestarted = true;
                    $this->restartConsumers();
                }
            }
        } catch (\Throwable $e) {
            $this->logger->error('[Klaviyo] Unable to clear plugin\'s cache, reason:' . $e);
        }
    }

    /**
     * Should restart consumers along with the scheduled tasks
     * @throws InvalidArgumentException
     * @see \Shopware\Core\Framework\MessageQueue\ScheduledTask\Command\ScheduledTaskRunner::shouldRestart
     */
    private function restartConsumers(): void
    {
        $cacheItem = $this->restartSignalCachePool->getItem(
            StopWorkerOnRestartSignalListener::RESTART_REQUESTED_TIMESTAMP_KEY
        );
        $cacheItem->set(microtime(true));
        $this->restartSignalCachePool->save($cacheItem);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SystemConfigChangedEvent::class => 'onSystemConfigurationChange'
        ];
    }
}
