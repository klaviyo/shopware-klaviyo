<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Gateway;

use Klaviyo\Integration\Klaviyo\Gateway\Exception\ProfilesListNotFoundException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;

class CachedGetListIdByListName implements GetListIdByListNameInterface
{
    public const CACHE_PREFIX = 'od_klaviyo_list_';

    private CacheItemPoolInterface $cache;

    public function __construct(
        CacheItemPoolInterface $cache
    ) {
        $this->cache = $cache;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function execute(string $salesChannelEntityId, string $listId): string
    {
        if (!$listId) {
            throw new ProfilesListNotFoundException('Subscription List ID is empty!');
        }

        $cacheKey = self::CACHE_PREFIX . $salesChannelEntityId;
        $cachedItem = $this->cache->getItem($cacheKey);

        if ($cachedItem->isHit()) {
            return (string) $cachedItem->get();
        }

        $cachedItem->expiresAfter(3600);
        $this->cache->save($cachedItem->set($listId));

        return $listId;
    }
}
