<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Promotion;

use Shopware\Core\Checkout\Promotion\PromotionCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class PromotionsExporter
{
    private EntityRepository $promotionRepository;

    public function __construct(EntityRepository $promotionRepository)
    {
        $this->promotionRepository = $promotionRepository;
    }

    public function exportToCSV(
        Context $context,
        string $salesChannelId = null,
        string $promotionId = null
    ): \SplFileObject {
        $promotions = $this->getPromotions($context, $salesChannelId, $promotionId);

        $tmpFileName = tempnam(sys_get_temp_dir(), 'shopware6_promotion_export');
        $fileObject = new \SplFileObject("$tmpFileName.csv", 'a+');

        $fileObject->fputcsv(['Coupon']);

        foreach ($promotions as $promotion) {
            if (null === $promotion->getCode() && ($promotion->getIndividualCodes()->count() > 0)) {
                foreach ($promotion->getIndividualCodes() as $individualCode) {
                    $fileObject->fputcsv([$individualCode->getCode()]);
                }
            } else {
                $fileObject->fputcsv([$promotion->getCode()]);
            }
        }

        $fileObject->fflush();

        return $fileObject;
    }

    private function getPromotions(
        Context $context,
        ?string $salesChannelId,
        string $promotionId = null
    ): PromotionCollection {
        $criteria = new Criteria();
        $criteria->addAssociation('individualCodes');

        if ($salesChannelId) {
            $criteria->addFilter(new EqualsFilter('salesChannels.id', $salesChannelId));
        }

        if ($promotionId) {
            $criteria->addFilter(new EqualsFilter('id', $promotionId));
        }

        /** @var PromotionCollection $promotionsCollection */
        $promotionsCollection = $this->promotionRepository
            ->search($criteria, $context)->getEntities();

        return $promotionsCollection;
    }
}
