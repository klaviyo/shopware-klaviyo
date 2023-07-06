<?php declare(strict_types=1);

namespace Od\Scheduler\Model\Job;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\{EqualsAnyFilter, EqualsFilter, OrFilter};

class JobTreeProvider
{
    public EntityRepository $jobRepository;

    public function __construct(EntityRepository $jobRepository)
    {
        $this->jobRepository = $jobRepository;
    }

    public function get(string $rootJobId, array $childStatuses = []): JobTree
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new OrFilter([
                new EqualsFilter('id', $rootJobId),
                new EqualsFilter('parentId', $rootJobId),
            ])
        );

        if (!empty($childStatuses)) {
            $criteria->addPostFilter(
                new OrFilter([
                    new EqualsFilter('parentId', null),
                    new EqualsAnyFilter('status', $childStatuses)
                ])
            );
        }

        return $this->loadTree($rootJobId, $criteria);
    }

    /**
     * @throws \Exception
     */
    public function loadTree(string $rootJobId, Criteria $criteria): JobTree
    {
        $context = Context::createDefaultContext();
        $searchResult = $this->jobRepository->search($criteria, $context);

        if (!$rootJob = $searchResult->getEntities()->get($rootJobId)) {
            throw new \Exception('No root job found!');
        }

        $childJobs = $searchResult->getEntities()->filterByProperty('parentId', $rootJobId)->getElements();

        return new JobTree($rootJob, $childJobs);
    }
}