<?php declare(strict_types=1);

namespace Klaviyo\Integration\Async\TaskHandler;

use Klaviyo\Integration\Async\Task\OldJobCleanupScheduledTask;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Psr\Log\LoggerInterface;

class OldJobCleanupScheduledTaskHandler extends ScheduledTaskHandler
{
    private EntityRepositoryInterface $jobRepository;
    private LoggerInterface $logger;
    private SystemConfigService $systemConfigService;

    public function __construct(
        EntityRepositoryInterface $scheduledTaskRepository,
        EntityRepositoryInterface $jobRepository,
        SystemConfigService $systemConfigService,
        LoggerInterface $logger
    ) {
        parent::__construct($scheduledTaskRepository);
        $this->jobRepository = $jobRepository;
        $this->systemConfigService = $systemConfigService;
        $this->logger = $logger;
    }

    public static function getHandledMessages(): iterable
    {
        return [OldJobCleanupScheduledTask::class];
    }

    public function run(): void
    {
        try {
            $isJobCleanupEnabled = $this->systemConfigService->getInt('KlaviyoIntegrationPlugin.config.oldJobCleanup');
            $dayPeriod = $this->systemConfigService->getInt('KlaviyoIntegrationPlugin.config.oldJobCleanupPeriod');

            if ($isJobCleanupEnabled && $dayPeriod) {
                $numberOfDaysBeforeToday = new \DateTime(' - ' . $dayPeriod . ' day');
                // Here we have context less task
                $context = new Context(new SystemSource());
                $criteria = new Criteria();
                $criteria->addFilter(new Filter\AndFilter([
                    new Filter\RangeFilter(
                        'createdAt',
                        ['lt' => $numberOfDaysBeforeToday->format(Defaults::STORAGE_DATE_FORMAT)]
                    ),
                    new Filter\ContainsFilter('type', 'od-klaviyo'),
                    new Filter\EqualsFilter('parentId', null)
                ]));

                // Formatting IDs array and deleting config keys
                $ids = \array_map(static function ($id) {
                    return ['id' => $id];
                }, $this->jobRepository->searchIds($criteria, $context)->getIds());

                $this->jobRepository->delete($ids, $context);
            }
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
