<?php declare(strict_types=1);

namespace Klaviyo\Integration\System\Job;

use Klaviyo\Integration\Entity\Job\JobEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

class JobHelper
{
    private EntityRepositoryInterface $jobRepository;

    public function __construct(EntityRepositoryInterface $jobRepository)
    {
        $this->jobRepository = $jobRepository;
    }

    public function getLastJob(Context $context, string $type): ?JobEntity
    {
        $criteria = new Criteria();
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));
        $criteria->addFilter(new EqualsFilter('type', $type));
        $criteria->setLimit(1);

        return $this->jobRepository->search($criteria, $context)->first();
    }

    public function getLastSucceedJob(Context $context, string $type): ?JobEntity
    {
        $criteria = new Criteria();
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));
        $criteria->addFilter(new EqualsFilter('status', JobEntity::STATUS_SUCCESS));
        $criteria->addFilter(new EqualsFilter('type', $type));
        $criteria->setLimit(1);

        return $this->jobRepository->search($criteria, $context)->first();
    }
}
