<?php declare(strict_types=1);

namespace Klaviyo\Integration\Utils;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Lifecycle
{
    private EntityRepository $systemConfigRepository;
    private Connection $connection;
    private ContainerInterface $container;
    private bool $hasOtherSchedulerDependency;

    public function __construct(
        ContainerInterface $container,
        bool $hasOtherSchedulerDependency
    ) {
        /** @var EntityRepository $systemConfigRepository */
        $systemConfigRepository = $container->get('system_config.repository');
        /** @var Connection $connection */
        $connection = $container->get(Connection::class);

        $this->container = $container;
        $this->hasOtherSchedulerDependency = $hasOtherSchedulerDependency;

        $this->systemConfigRepository = $systemConfigRepository;
        $this->connection = $connection;
    }

    public function uninstall(UninstallContext $context): void
    {
        if ($this->hasOtherSchedulerDependency) {
            $this->removePendingJobs();
        } else {
            // TODO: OdScheduler must be responsible for its uninstallation - move such operations to it in future.
            $this->connection->executeStatement('DROP TABLE IF EXISTS `od_scheduler_job_message`');
            $this->connection->executeStatement('DROP TABLE IF EXISTS `od_scheduler_job`');

            $schedulerMigrationClassWildcard = addcslashes('Od\Scheduler\Migration', '\\_%') . '%';
            $this->connection->executeUpdate(
                'DELETE FROM migration WHERE class LIKE :class',
                ['class' => $schedulerMigrationClassWildcard]
            );
        }

        $this->removeConfigs($context->getContext());
        $this->removeTables();
    }

    public function removePendingJobs()
    {
        $this->connection->executeStatement(
            "DELETE from `od_scheduler_job` WHERE `type` LIKE :prefix",
            [
                'prefix' => 'od-klaviyo%',
            ],
        );
    }

    public function removeConfigs(Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new ContainsFilter('configurationKey', 'klavi_overd'));
        $configIds = $this->systemConfigRepository->searchIds($criteria, $context)->getIds();
        $configIds = \array_map(static function ($id) {
            return ['id' => $id];
        }, $configIds);

        if (!empty($configIds)) {
            $this->systemConfigRepository->delete(array_values($configIds), $context);
        }
    }

    public function removeTables(): void
    {
        $this->connection->executeStatement('DROP TABLE IF EXISTS `klaviyo_job_event`');
        $this->connection->executeStatement('DROP TABLE IF EXISTS `klaviyo_job_cart_request`');
        $this->connection->executeStatement('DROP TABLE IF EXISTS `klaviyo_flag_storage`');
    }
}
