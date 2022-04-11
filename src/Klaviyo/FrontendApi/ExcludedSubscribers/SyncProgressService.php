<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\FrontendApi\ExcludedSubscribers;

use Klaviyo\Integration\Klaviyo\FrontendApi\DTO\SyncProgressInfo;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\{EqualsFilter, OrFilter};
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class SyncProgressService
{
    public const LAST_SYNCHRONIZED_UNSUBSCRIBERS_PAGE = 'last_synchronized_unsubscribers_page';
    public const LAST_SYNCHRONIZED_UNSUBSCRIBERS_PAGE_HASH = 'last_synchronized_unsubscribers_page_hash';

    private EntityRepositoryInterface $klaviyoFlagStorageRepository;

    public function __construct(EntityRepositoryInterface $klaviyoFlagStorageRepository)
    {
        $this->klaviyoFlagStorageRepository = $klaviyoFlagStorageRepository;
    }

    public function get(Context $context, SalesChannelEntity $channel): ?SyncProgressInfo
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salesChannelId', $channel->getId()));
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));
        $criteria->addFilter(new OrFilter([
            new EqualsFilter('key', self::LAST_SYNCHRONIZED_UNSUBSCRIBERS_PAGE_HASH),
            new EqualsFilter('key', self::LAST_SYNCHRONIZED_UNSUBSCRIBERS_PAGE)
        ]));
        $criteria->setLimit(2);

        $klaviyoFlags = $this->klaviyoFlagStorageRepository->search($criteria, $context)->getEntities();
        $lastPageAndHash = [];
        foreach ($klaviyoFlags ?? [] as $flag) {
            $lastPageAndHash[$flag->getKey()] = $flag->getValue();
        }

        $page = $lastPageAndHash ? (int)$lastPageAndHash[self::LAST_SYNCHRONIZED_UNSUBSCRIBERS_PAGE] : 0;
        $hash = $lastPageAndHash ? $lastPageAndHash[self::LAST_SYNCHRONIZED_UNSUBSCRIBERS_PAGE_HASH] : '';

        return new SyncProgressInfo($page, $hash, $channel->getId());
    }

    public function save(SyncProgressInfo $progressInfo)
    {
        $context = Context::createDefaultContext();
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('value', $progressInfo->getHash()));
        $criteria->addFilter(new EqualsFilter('salesChannelId', $progressInfo->getSalesChannelId()));
        $this->klaviyoFlagStorageRepository->create([
            [
                'id' => Uuid::randomHex(),
                'key' => self::LAST_SYNCHRONIZED_UNSUBSCRIBERS_PAGE,
                'value' => (string)$progressInfo->getPage(),
                'salesChannelId' => $progressInfo->getSalesChannelId()
            ],
            [
                'id' => Uuid::randomHex(),
                'key' => self::LAST_SYNCHRONIZED_UNSUBSCRIBERS_PAGE_HASH,
                'value' => $progressInfo->getHash(),
                'salesChannelId' => $progressInfo->getSalesChannelId()
            ]
        ], $context);
    }
}