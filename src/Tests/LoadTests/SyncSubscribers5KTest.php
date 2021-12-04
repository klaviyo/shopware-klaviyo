<?php declare(strict_types=1);

namespace Klaviyo\Integration\Tests\LoadTests;

use Klaviyo\Integration\Entity\Job\JobEntity;
use Klaviyo\Integration\Job\VirtualProxyJobScheduler;
use Klaviyo\Integration\Subscriber\Job\FullResynchronizationJobProcessor;
use Klaviyo\Integration\Test\KlaviyoSubscriberManagement;
use Klaviyo\Integration\Tests\AbstractIntegrationTestCase;
use Klaviyo\Integration\Tests\DataFixtures;
use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class SyncSubscribers5KTest extends AbstractIntegrationTestCase
{
    private SalesChannelEntity $salesChannelEntity;
    private KlaviyoSubscriberManagement $subscriberManagement;
    private FullResynchronizationJobProcessor $fullJobProcessor;
    private VirtualProxyJobScheduler $jobScheduler;
    private string $currentListId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->executeFixtures([
            new DataFixtures\RegisterDefaultSalesChannel(),
            new DataFixtures\Subscriber\GenerateNewsletterRecipients(5000)
        ]);

        $this->salesChannelEntity = $this->getByReference('klaviyo_tracking_integration.sales_channel.storefront');
        $klaviyoGateway = $this->getContainer()->get('klaviyo.tracking_integration.gateway.test.public');
        $this->fullJobProcessor = $this->getContainer()->get('klaviyo.tracking_integration.job.full.processor.test.public');
        $this->jobScheduler = $this->getContainer()->get(VirtualProxyJobScheduler::class);

        $this->subscriberManagement = new KlaviyoSubscriberManagement($this->salesChannelEntity, $klaviyoGateway);
        $this->subscriberManagement->deleteKlaviyoTestList(KLAVIYO_LIST_NAME);
        $this->currentListId =  $this->subscriberManagement->createKlaviyoTestList(KLAVIYO_LIST_NAME);
    }

    public function testSyncBigAmountOfSubscribers()
    {
        $defaultContext = Context::createDefaultContext();
        $this->jobScheduler->scheduleJob($defaultContext, JobEntity::SUBSCRIBERS_SYNCHRONIZATION_TYPE);
        /** @var JobEntity $newJob */
        $newJob = $this->getJobs()->last();

        self::assertEquals(JobEntity::STATUS_PENDING, $newJob->getStatus());

        $this->fullJobProcessor->process($defaultContext, $newJob);

        /** @var EntityRepositoryInterface $recipientRepository */
        $recipientRepository = $this->getContainer()->get('newsletter_recipient.repository');
        $generatedRecipientsEmails = $recipientRepository->search(new Criteria(), $defaultContext)->getEntities();
        $generatedRecipientsEmails = array_values(array_map(function (NewsletterRecipientEntity $recipientEntity) {
            return $recipientEntity->getEmail();
        }, $generatedRecipientsEmails->getElements()));

        /**
         * This sleep trick is required because of Klaviyo API Rate limiting.
         * So I believe we cannot send repeatable requests to different API endpoints without delay.
         */
        sleep(30);

        $recipientsFromKlaviyo = array_map(function ($memberData) {
            return $memberData['email'] ?? 'undefined_email';
        },  $this->subscriberManagement->getKlaviyoListMembers($this->currentListId));

        self::assertEquals(
            $generatedRecipientsEmails,
            $recipientsFromKlaviyo,
            'Some emails are missing in Klaviyo List'
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->subscriberManagement->deleteKlaviyoTestList(KLAVIYO_LIST_NAME);
    }
}
