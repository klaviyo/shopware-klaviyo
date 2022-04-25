<?php declare(strict_types=1);

namespace Klaviyo\Integration\Utils;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

class Lifecycle
{
    private EntityRepositoryInterface $systemConfigRepository;
    private Connection $connection;

    public function __construct(
        EntityRepositoryInterface $systemConfigRepository,
        Connection $connection
    ) {
        $this->systemConfigRepository = $systemConfigRepository;
        $this->connection = $connection;
    }

    public function uninstall(UninstallContext $context): void
    {
        $this->removeConfigs($context->getContext());
        $this->removeTables();
    }

    public function removeConfigs(Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new ContainsFilter('configurationKey', 'KlaviyoIntegrationPlugin'));
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
