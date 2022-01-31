<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Gateway;

use Psr\Cache\CacheItemPoolInterface;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class CachedGetListIdByListName implements GetListIdByListNameInterface
{
    private CacheItemPoolInterface $cache;
    private GetListIdByListName $getListIdByListName;

    public function __construct(CacheItemPoolInterface $cache, GetListIdByListName $getListIdByListName)
    {
        $this->cache = $cache;
        $this->getListIdByListName = $getListIdByListName;
    }

    public function execute(SalesChannelEntity $salesChannelEntity, string $listName): string
    {
        $cacheKey = 'od_klaviyo_list_' . $salesChannelEntity->getId();
        $cachedItem = $this->cache->getItem($cacheKey);

        if ($cachedItem->isHit()) {
            return $cachedItem->get();
        }

        $klaviyoList = $this->getListIdByListName->execute($salesChannelEntity, $listName);
        $this->cache->save($cachedItem->set($klaviyoList));

        return $klaviyoList;
    }
}