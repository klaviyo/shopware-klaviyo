<?php

namespace Klaviyo\Integration\Tests;

use Doctrine\DBAL\Connection;
use GuzzleHttp\ClientInterface;
use Klaviyo\Integration\Entity\Job\JobCollection;
use Klaviyo\Integration\Entity\Job\JobEntity;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Klaviyo\Integration\Tests\DataFixtures\DataFixturesExecutor;
use Klaviyo\Integration\Tests\DataFixtures\ReferencesRegistry;
use Klaviyo\Integration\Test\Mock\GuzzleClientTestProxy;

abstract class AbstractTestCase extends TestCase
{
    protected const PRIVATE_API_KEY_STUB = 'private_api_key_stub';
    protected const PUBLIC_API_KEY_STUB = 'public_api_key_stub';
    protected const KLAVIYO_LIST_FOR_SUBSCRIBERS_SYNC = 'test list name';

    use IntegrationTestBehaviour;

    protected SystemConfigService $systemConfigService;
    protected Connection $connection;
    protected MockObject $guzzleClient;
    protected DataFixturesExecutor $dataFixturesExecutor;
    protected ReferencesRegistry $referenceRegistry;
    protected EntityRepositoryInterface $jobRepository;

    protected function setUp(): void
    {
        $this->referenceRegistry = new ReferencesRegistry();
        $this->dataFixturesExecutor = new DataFixturesExecutor($this->referenceRegistry);
        $this->jobRepository = $this->getContainer()->get('klaviyo_job.repository');

        $container = $this->getContainer();

        /** @var Connection $connection */
        $connection = $container->get(Connection::class);
        $this->connection = $connection;

        /** @var SystemConfigService $systemConfigService */
        $systemConfigService = $this->getContainer()->get(SystemConfigService::class);
        $this->systemConfigService = $systemConfigService;

        $this->setKlaviyoSystemSettings();

        $this->guzzleClient = $this->createMock(ClientInterface::class);

        /** @var GuzzleClientTestProxy $client */
        $client = $this->getContainer()->get('klaviyo.tracking_integration.guzzle_client.test.public');
        $client->setProxiedClient($this->guzzleClient);
    }

    protected function executeFixtures(array $fixtures)
    {
        $this->dataFixturesExecutor->executeDataFixtures($this->getContainer(), $fixtures);
    }

    protected function setKlaviyoSystemSettings()
    {
        $this->systemConfigService->set('KlaviyoIntegrationPlugin.config.privateApiKey', self::PRIVATE_API_KEY_STUB);
        $this->systemConfigService->set('KlaviyoIntegrationPlugin.config.publicApiKey', self::PUBLIC_API_KEY_STUB);
        $this->systemConfigService->set(
            'KlaviyoIntegrationPlugin.config.klaviyoListForSubscribersSync',
            self::KLAVIYO_LIST_FOR_SUBSCRIBERS_SYNC
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

    protected function getSingleJob(): JobEntity
    {
        $jobs = $this->getJobs();

        return $jobs->first();
    }

    protected function getJobs(): JobCollection
    {
        $criteria = new Criteria();
        $criteria->addSorting(new FieldSorting('createdAt', 'ASC'));
        return $this->jobRepository->search($criteria, Context::createDefaultContext())->getEntities();
    }
}
