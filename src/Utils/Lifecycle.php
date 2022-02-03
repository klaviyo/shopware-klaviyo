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

    public const SYSTEM_CONFIG_DOMAIN = 'KlaviyoIntegrationPlugin';

    private EntityRepositoryInterface $systemConfigRepository;

    private Connection $connection;

    /**
     * @param EntityRepositoryInterface $systemConfigRepository
     * @param Connection $connection
     */
    public function __construct(
        EntityRepositoryInterface $systemConfigRepository,
        Connection                $connection
    )
    {
        $this->systemConfigRepository = $systemConfigRepository;
        $this->connection = $connection;
    }

    /**
     * @param UninstallContext $context
     * @return void
     * @throws \Doctrine\DBAL\Exception
     */
    public function uninstall(UninstallContext $context): void
    {
        $this->removeConfigs($context->getContext());
        $this->removeTables();
    }

    /**
     * @param Context $context
     * @return void
     */
    public function removeConfigs(Context $context): void
    {
        $criteria = (new Criteria())
            ->addFilter(new ContainsFilter('configurationKey', self::SYSTEM_CONFIG_DOMAIN));
        $result = $this->systemConfigRepository->searchIds($criteria, $context);

        $ids = \array_map(static function ($id) {
            return ['id' => $id];
        }, $result->getIds());

        if ($ids === []) {
            return;
        }

        $this->systemConfigRepository->delete($ids, $context);
    }

    /**
     * @return void
     * @throws \Doctrine\DBAL\Exception
     */
    public function removeTables(): void
    {
        $this->connection->executeStatement('DROP TABLE IF EXISTS `klaviyo_job_event`');
        $this->connection->executeStatement('DROP TABLE IF EXISTS `klaviyo_job_cart_request`');
    }

}
