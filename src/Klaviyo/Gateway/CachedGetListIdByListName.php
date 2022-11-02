<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Gateway;

use Psr\Cache\CacheItemPoolInterface;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

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

    public function execute(SalesChannelEntity $salesChannelEntity, string $listName): string
    {
        $cacheKey = self::CACHE_PREFIX . $salesChannelEntity->getId();
        $cachedItem = $this->cache->getItem($cacheKey);

        if ($cachedItem->isHit()) {
            return (string)$cachedItem->get();
        }

        $klaviyoList = $this->getListIdByListName->execute($salesChannelEntity, $listName);
        $cachedItem->expiresAfter(3600);
        $this->cache->save($cachedItem->set($klaviyoList));

        return $klaviyoList;
    }
}
