<?php

namespace Klaviyo\Integration\EventListener;

use Psr\Cache\CacheItemPoolInterface;
use Shopware\Core\System\SystemConfig\Event\SystemConfigChangedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\EventListener\StopWorkerOnRestartSignalListener;

class RestartConsumerAfterPluginConfigChangedEventListener implements EventSubscriberInterface
{
    private CacheItemPoolInterface $restartSignalCachePool;

    public function __construct(
        CacheItemPoolInterface $restartSignalCachePool
    ) {
        $this->restartSignalCachePool = $restartSignalCachePool;
    }

    public function onSystemConfigurationChange(SystemConfigChangedEvent $systemConfigChangedEvent)
    {
        $key = $systemConfigChangedEvent->getKey();
        if (strpos($key, 'KlaviyoIntegrationPlugin.config') === 0) {
            $this->restartConsumers();
        }
    }

    /**
     * Should restart consumers along with the scheduled tasks
     * @see \Shopware\Core\Framework\MessageQueue\ScheduledTask\Command\ScheduledTaskRunner::shouldRestart
     */
    private function restartConsumers()
    {
        $cacheItem = $this->restartSignalCachePool->getItem(StopWorkerOnRestartSignalListener::RESTART_REQUESTED_TIMESTAMP_KEY);
        $cacheItem->set(microtime(true));
        $this->restartSignalCachePool->save($cacheItem);
    }

    public static function getSubscribedEvents()
    {
        return [
            SystemConfigChangedEvent::class => 'onSystemConfigurationChange'
        ];
    }
}