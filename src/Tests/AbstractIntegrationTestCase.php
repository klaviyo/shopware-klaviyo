<?php declare(strict_types=1);

namespace Klaviyo\Integration\Tests;

use Klaviyo\Integration\Entity\Job\JobCollection;
use Klaviyo\Integration\Tests\DataFixtures\DataFixturesExecutor;
use Klaviyo\Integration\Tests\DataFixtures\ReferencesRegistry;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Shopware\Core\System\SystemConfig\SystemConfigService;

abstract class AbstractIntegrationTestCase extends TestCase
{
    use IntegrationTestBehaviour;

    protected SystemConfigService $systemConfigService;
    protected DataFixturesExecutor $dataFixturesExecutor;
    protected ReferencesRegistry $referenceRegistry;
    protected EntityRepositoryInterface $jobRepository;

    protected function setUp(): void
    {
        if (!defined('KLAVIYO_PRIVATE_KEY') || KLAVIYO_PRIVATE_KEY === 's$cretf0rt3st') {
            $this->markTestSkipped(
                'KLAVIYO_PRIVATE_KEY constant must be configured in phpunit.xml.dist. to perform Load Testsuite'
            );
        }

        if (!defined('KLAVIYO_LIST_NAME')) {
            /** Fallback to default list name for testing purposes */
            define('KLAVIYO_LIST_NAME', 'phpunit_testing_list');
        }

        $this->referenceRegistry = new ReferencesRegistry();
        $this->dataFixturesExecutor = new DataFixturesExecutor($this->referenceRegistry);
        $this->jobRepository = $this->getContainer()->get('klaviyo_job.repository');

        /** @var SystemConfigService $systemConfigService */
        $systemConfigService = $this->getContainer()->get(SystemConfigService::class);
        $this->systemConfigService = $systemConfigService;
        $this->setKlaviyoSystemSettings();
    }

    protected function executeFixtures(array $fixtures)
    {
        $this->dataFixturesExecutor->executeDataFixtures($this->getContainer(), $fixtures);
    }

    protected function setKlaviyoSystemSettings()
    {
        $this->systemConfigService->set('KlaviyoIntegrationPlugin.config.privateApiKey', KLAVIYO_PRIVATE_KEY);
        $this->systemConfigService->set(
            'KlaviyoIntegrationPlugin.config.klaviyoListForSubscribersSync',
            KLAVIYO_LIST_NAME
        );
    }

    protected function getByReference(string $reference): Entity
    {
        return $this->referenceRegistry->getByReference($reference);
    }

    protected function setByReference(string $reference, $value)
    {
        $this->referenceRegistry->setReference($reference, $value);
    }

    /**
     * Isolation is not working properly in some cases so it is safer to clear container after test
     * to avoid problems with the isolation
     */
    public static function tearDownAfterClass(): void
    {
        KernelLifecycleManager::ensureKernelShutdown();
    }

    protected function getJobs(): JobCollection
    {
        $criteria = new Criteria();
        $criteria->addSorting(new FieldSorting('createdAt', 'ASC'));
        return $this->jobRepository->search($criteria, Context::createDefaultContext())->getEntities();
    }
}
