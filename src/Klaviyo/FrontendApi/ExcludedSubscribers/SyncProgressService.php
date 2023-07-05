<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\FrontendApi\ExcludedSubscribers;

use Klaviyo\Integration\Entity\FlagStorage\FlagStorageEntity;
use Klaviyo\Integration\Klaviyo\FrontendApi\DTO\SyncProgressInfo;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\{EqualsAnyFilter, EqualsFilter};
use Shopware\Core\Framework\DataAbstractionLayer\Search\Grouping\FieldGrouping;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;

class SyncProgressService
{
    public const UNSUB_PAGE = 'sync_unsub_page';
    public const UNSUB_PAGE_HASH = 'sync_unsub_page_hash';

    private EntityRepository $klaviyoFlagStorageRepository;

    public function __construct(EntityRepository $klaviyoFlagStorageRepository)
    {
        $this->klaviyoFlagStorageRepository = $klaviyoFlagStorageRepository;
    }

    public function get(Context $context, string $channelId): ?SyncProgressInfo
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salesChannelId', $channelId));
        $criteria->addFilter(new EqualsAnyFilter('key', [self::UNSUB_PAGE_HASH, self::UNSUB_PAGE]));
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));
        $criteria->addGroupField(new FieldGrouping('key'));
        $unsubInfoFlags = $this->klaviyoFlagStorageRepository->search($criteria, $context)->getEntities();

        $hashFlag = $unsubInfoFlags->filter(fn(FlagStorageEntity $flag) => $flag->getKey() === self::UNSUB_PAGE_HASH)
            ->first() ?? $this->createFlagEntity(self::UNSUB_PAGE_HASH);
        $pageFlag = $unsubInfoFlags->filter(fn(FlagStorageEntity $flag) => $flag->getKey() === self::UNSUB_PAGE)
            ->first() ?? $this->createFlagEntity(self::UNSUB_PAGE);

        return new SyncProgressInfo($pageFlag, $hashFlag, $channelId);
    }

    public function save(Context $context, SyncProgressInfo $progressInfo)
    {
        $this->klaviyoFlagStorageRepository->upsert([
            [
                'id' => $progressInfo->getPageFlagEntity()->getId() ?: Uuid::randomHex(),
                'key' => self::UNSUB_PAGE,
                'value' => (string)$progressInfo->getPage(),
                'salesChannelId' => $progressInfo->getSalesChannelId()
            ],
            [
                'id' =>  $progressInfo->getHashFlagEntity()->getId() ?: Uuid::randomHex(),
                'key' => self::UNSUB_PAGE_HASH,
                'value' => $progressInfo->getHash(),
                'salesChannelId' => $progressInfo->getSalesChannelId()
            ]
        ], $context);
    }

    private function createFlagEntity(string $key): FlagStorageEntity
    {
        $flagEntity = new FlagStorageEntity();
        $flagEntity->setValue('');
        $flagEntity->setKey($key);

        return $flagEntity;
    }
}
