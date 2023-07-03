<?php

namespace Klaviyo\Integration\Klaviyo\Promotion;

use Shopware\Core\Checkout\Promotion\PromotionCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;

class PromotionsExporter
{
    private EntityRepository $promotionRepository;

    public function __construct(EntityRepository $promotionRepository)
    {
        $this->promotionRepository = $promotionRepository;
    }

    /**
     * @return \SplFileObject
     */
    public function exportToCSV(Context $context, ?string $salesChannelId = null): \SplFileObject
    {
        $promotions = $this->getPromotions($context, $salesChannelId);

        $tmpFileName = tempnam(sys_get_temp_dir(), "shopware6_promotion_export");
        $fileObject = new \SplFileObject("$tmpFileName.csv", 'a+');

        $fileObject->fputcsv(['Coupon']);
        foreach ($promotions as $promotion) {
            $fileObject->fputcsv([$promotion->getCode()]);
        }
        $fileObject->fflush();

        return $fileObject;
    }

    private function getPromotions(Context $context, ?string $salesChannelId): PromotionCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new NotFilter(NotFilter::CONNECTION_AND, [new EqualsFilter('code', null)])
        );

        if ($salesChannelId) {
            $criteria->addFilter(new EqualsFilter('salesChannels.id', $salesChannelId));
        }

        /** @var PromotionCollection $promotionsCollection */
        $promotionsCollection = $this->promotionRepository
            ->search($criteria, $context)->getEntities();

        return $promotionsCollection;
    }
}
