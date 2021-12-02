<?php declare(strict_types=1);

namespace Klaviyo\Integration\Tests\LoadTests;

use GuzzleHttp\Client;
use Klaviyo\Integration\Entity\Job\JobEntity;
use Klaviyo\Integration\Job\VirtualProxyJobScheduler;
use Klaviyo\Integration\Klaviyo\Gateway\Exception\ProfilesListNotFoundException;
use Klaviyo\Integration\Klaviyo\Gateway\KlaviyoGateway;
use Klaviyo\Integration\Subscriber\Job\FullResynchronizationJobProcessor;
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
    private KlaviyoGateway $klaviyoGateway;
    private FullResynchronizationJobProcessor $fullJobProcessor;
    private Client $guzzleClient;
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
        $this->klaviyoGateway = $this->getContainer()->get('klaviyo.tracking_integration.gateway.test.public');
        $this->fullJobProcessor = $this->getContainer()->get('klaviyo.tracking_integration.job.full.processor.test.public');
        $this->jobScheduler = $this->getContainer()->get(VirtualProxyJobScheduler::class);
        $this->guzzleClient = new Client();

        $this->deleteKlaviyoTestList(KLAVIYO_LIST_NAME);
        $this->currentListId = $this->createKlaviyoTestList(KLAVIYO_LIST_NAME);
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
        }, $this->getKlaviyoListMembers());
        $emailsDiff = array_diff($generatedRecipientsEmails, $recipientsFromKlaviyo);

        self::assertEquals(
            $generatedRecipientsEmails,
            $recipientsFromKlaviyo,
            'Some emails are missing in Klaviyo List' . (count($emailsDiff) < 5 ? ': ' . implode(',', $emailsDiff) : '')
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->deleteKlaviyoTestList(KLAVIYO_LIST_NAME);
    }

    private function getKlaviyoListMembers(int $marker = 0): array
    {
        $urlSchema = $marker === 0
            ? 'https://a.klaviyo.com/api/v2/group/%s/members/all?api_key=%s'
            : 'https://a.klaviyo.com/api/v2/group/%s/members/all?api_key=%s&marker=%s';
        $response = $this->guzzleClient->get(
            sprintf($urlSchema, $this->currentListId, KLAVIYO_PRIVATE_KEY, $marker),
        );

        $responseData = json_decode($response->getBody()->getContents(), true);
        $marker = $responseData['marker'] ?? 0;
        $records = $responseData['records'] ?: [];

        if ($marker) {
            $records = array_merge($records, $this->getKlaviyoListMembers($marker));
        }

        return $records;
    }

    private function deleteKlaviyoTestList(string $listName): void
    {
        try {
            $listId = $this->klaviyoGateway->getListIdByListName($this->salesChannelEntity, $listName);
            $request = new \GuzzleHttp\Psr7\Request(
                'DELETE',
                sprintf('https://a.klaviyo.com/api/v2/list/%s?api_key=%s', $listId, KLAVIYO_PRIVATE_KEY)
            );

            $this->guzzleClient->sendRequest($request);
        } catch (ProfilesListNotFoundException $e) {
            null;
        }
    }

    private function createKlaviyoTestList(string $listName): string
    {
        $requestBodyObject = new \stdClass();
        $requestBodyObject->list_name = $listName;

        $response = $this->guzzleClient->request(
            'POST',
            sprintf('https://a.klaviyo.com/api/v2/lists?api_key=%s', KLAVIYO_PRIVATE_KEY),
            [
                \GuzzleHttp\RequestOptions::HEADERS => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ],
                \GuzzleHttp\RequestOptions::FORM_PARAMS => [
                    'list_name' => $listName
                ]
            ]
        );
        $responseData = json_decode($response->getBody()->getContents(), true);

        return (string)$responseData['list_id'];
    }
}
