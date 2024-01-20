<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Gateway;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;

class CachedGetListIdByListName implements GetListIdByListNameInterface
{
    public const CACHE_PREFIX = 'od_klaviyo_list_';

    private CacheItemPoolInterface $cache;
    private GetListIdByListName $getListIdByListName;

    public function __construct(
        CacheItemPoolInterface $cache,
        GetListIdByListName $getListIdByListName
    ) {
        $this->cache = $cache;
        $this->getListIdByListName = $getListIdByListName;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function execute(string $salesChannelEntityId, string $listName): string
    {
        $cacheKey = self::CACHE_PREFIX . $salesChannelEntityId;
        $cachedItem = $this->cache->getItem($cacheKey);

        if ($cachedItem->isHit()) {
            return (string) $cachedItem->get();
        }

        $klaviyoList = $this->getListIdByListName->execute($salesChannelEntityId, $listName);
        $cachedItem->expiresAfter(3600);
        $this->cache->save($cachedItem->set($klaviyoList));

        return $klaviyoList;
    }
}
