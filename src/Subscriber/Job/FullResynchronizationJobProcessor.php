<?php

namespace Klaviyo\Integration\Subscriber\Job;

use Klaviyo\Integration\Configuration\ConfigurationRegistry;
use Klaviyo\Integration\Entity\Helper\JobHelper;
use Klaviyo\Integration\Entity\Job\JobEntity;
use Klaviyo\Integration\Job\AbstractJobProcessor;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common\ProfileContactInfoCollection;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common\ProfileInfo;
use Klaviyo\Integration\Klaviyo\Gateway\KlaviyoGateway;
use Klaviyo\Integration\Utils\Batch\Batch;
use Klaviyo\Integration\Utils\Logger\ContextHelper;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientCollection;
use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientEntity;
use Shopware\Core\Content\Newsletter\SalesChannel\NewsletterSubscribeRoute;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class FullResynchronizationJobProcessor extends AbstractJobProcessor
{
    public const DEFAULT_SUBSCRIBERS_EXPORT_CHUNK_SIZE = 100;

    private KlaviyoGateway $klaviyoGateway;
    private EntityRepositoryInterface $salesChannelRepository;
    private EntityRepositoryInterface $newsletterRecipientRepository;
    private ConfigurationRegistry $configurationRegistry;
    private LoggerInterface $logger;
    private int $subscribersExportChunkSize;

    public function __construct(
        KlaviyoGateway $klaviyoGateway,
        EntityRepositoryInterface $salesChannelRepository,
        EntityRepositoryInterface $newsletterRecipientRepository,
        JobHelper $jobHelper,
        ConfigurationRegistry $configurationRegistry,
        LoggerInterface $logger,
        $subscribersExportChunkSize = self::DEFAULT_SUBSCRIBERS_EXPORT_CHUNK_SIZE
    ) {
        $this->klaviyoGateway = $klaviyoGateway;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->newsletterRecipientRepository = $newsletterRecipientRepository;
        $this->configurationRegistry = $configurationRegistry;
        $this->logger = $logger;
        $this->subscribersExportChunkSize = $subscribersExportChunkSize;

        parent::__construct($jobHelper);
    }

    /**
     * @param Context $context
     * @param JobEntity $job
     *
     * @return bool
     * @throws \Klaviyo\Integration\Klaviyo\Client\Exception\ClientException
     * @throws \Throwable
     */
    protected function doProcess(Context $context, JobEntity $job): bool
    {
        $channels = $this->salesChannelRepository->search(new Criteria(), $context);

        $success = true;
        /** @var SalesChannelEntity $channel */
        foreach ($channels as $channel) {
            if (!$this->processSalesChannelSubscribers($context, $channel)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * @param Context $context
     * @param SalesChannelEntity $salesChannelEntity
     *
     * @return bool
     * @throws \Klaviyo\Integration\Klaviyo\Client\Exception\ClientException
     * @throws \Throwable
     */
    protected function processSalesChannelSubscribers(Context $context, SalesChannelEntity $salesChannelEntity): bool
    {
        $listId = $this->klaviyoGateway->getListIdByListName(
            $salesChannelEntity,
            $this->configurationRegistry->getConfiguration($salesChannelEntity)->getSubscribersListName()
        );

        $isRemoveExtraProfilesFromKlaviyoListSuccess = $this->removeExtraProfilesFromKlaviyoList(
            $context,
            $salesChannelEntity,
            $listId
        );
        $isAddShopwareSubscribersToKlaviyoSuccess = $this->addShopwareSubscribersToKlaviyo(
            $context,
            $salesChannelEntity,
            $listId
        );

        return $isAddShopwareSubscribersToKlaviyoSuccess && $isRemoveExtraProfilesFromKlaviyoListSuccess;
    }

    private function removeExtraProfilesFromKlaviyoList(
        Context $context,
        SalesChannelEntity $salesChannelEntity,
        string $listId
    ): bool {
        try {
            $generator = $this->klaviyoGateway->getKlaviyoListMembersGenerator($salesChannelEntity, $listId);

            $subscribersForRemove = new ProfileContactInfoCollection();
            $collectSubscribersForRemove = function (array $items) use (&$subscribersForRemove, $context) {
                $emails = [];
                /** @var ProfileInfo $item */
                foreach ($items as $item) {
                    $emails[] = $item->getEmail();
                }

                $criteria = new Criteria();
                // Condition will be case-insensitive because of the column collation
                $criteria->addFilter(new EqualsAnyFilter('email', $emails));
                $criteria->addFilter(
                    new EqualsAnyFilter(
                        'status',
                        [
                            NewsletterSubscribeRoute::STATUS_OPT_IN,
                            NewsletterSubscribeRoute::STATUS_DIRECT
                        ]
                    )
                );
                $foundSubscribers = $this->newsletterRecipientRepository->search($criteria, $context)->getEntities();

                $foundSubscribersEmailsHashMap = [];
                /** @var NewsletterRecipientEntity $subscriber */
                foreach ($foundSubscribers as $subscriber) {
                    $emailLowercase = mb_strtolower($subscriber->getEmail());
                    $foundSubscribersEmailsHashMap[$emailLowercase] = true;
                }

                foreach ($items as $item) {
                    $emailLowercase = $item->getEmail();
                    if (!isset($foundSubscribersEmailsHashMap[$emailLowercase])) {
                        $subscribersForRemove->add($item);
                    }
                }
            };

            $batch = new Batch(1000, $collectSubscribersForRemove);
            foreach ($generator as $item) {
                $batch->add($item);
            }
            $batch->flush();

            /**
             * We remove subscribers after finish iteration of the List subscribers to avoid problems related to
             * iteration of the changed data
             */
            $collections = $subscribersForRemove->split(1000);

            $isSuccess = true;
            foreach ($collections as $collection) {
                $isRemoveIterationSuccess = $this->klaviyoGateway->removeKlaviyoSubscribersFromList(
                    $salesChannelEntity,
                    $collection,
                    $listId
                );
                if (!$isRemoveIterationSuccess) {
                    $isSuccess = false;
                }
            }

            return $isSuccess;
        } catch (\Throwable $throwable) {
            $this->logger->error(
                sprintf('Failed to remove extra profiles from Klaviyo list. Reason: %s', $throwable->getMessage()),
                ContextHelper::createContextFromException($throwable)
            );

            return false;
        }
    }

    private function addShopwareSubscribersToKlaviyo(
        Context $context,
        SalesChannelEntity $salesChannelEntity,
        string $listId
    ): bool {
        try {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannelEntity->getId()));
            $criteria->addFilter(
                new EqualsAnyFilter(
                    'status',
                    [
                        NewsletterSubscribeRoute::STATUS_OPT_IN,
                        NewsletterSubscribeRoute::STATUS_DIRECT
                    ]
                )
            );

            $success = true;

            $ids = $this->newsletterRecipientRepository->searchIds($criteria, $context)->getIds();
            // Use butches to avoid memory limit and performance issues
            $idChunks = array_chunk($ids, $this->subscribersExportChunkSize);
            foreach ($idChunks as $ids) {
                /** @var NewsletterRecipientCollection $recipients */
                $recipients = $this->newsletterRecipientRepository
                    ->search(new Criteria($ids), $context)
                    ->getEntities();
                if (!$this->klaviyoGateway->addToKlaviyoProfilesList($salesChannelEntity, $recipients, $listId)) {
                    $success = false;
                }
            }

            return $success;
        } catch (\Throwable $throwable) {
            $this->logger->error(
                sprintf('Failed to add subscribers to Klaviyo list. Reason: %s', $throwable->getMessage()),
                ContextHelper::createContextFromException($throwable)
            );

            return false;
        }
    }

    public function isApplicable(Context $context, JobEntity $job): bool
    {
        return $job->getType() === JobEntity::SUBSCRIBERS_SYNCHRONIZATION_TYPE;
    }
}