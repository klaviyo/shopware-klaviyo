<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Gateway;

use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Symfony\Component\Cache\Adapter\AdapterInterface;

class CachedGetListIdByListName implements GetListIdByListNameInterface
{
    private AdapterInterface $cache;
    private GetListIdByListName $getListIdByListName;

    public function __construct(
        AdapterInterface $cache,
        GetListIdByListName $getListIdByListName
    ) {
        $this->cache = $cache;
        $this->getListIdByListName = $getListIdByListName;
    }

    public function execute(SalesChannelEntity $salesChannelEntity, string $listName): string
    {
        $cacheKey = 'od_klaviyo_list_' . $salesChannelEntity->getId();
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
