<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase\Operation;

use Klaviyo\Integration\Async\Message\SubscriberSyncMessage;
use Klaviyo\Integration\Configuration\ConfigurationRegistry;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common\ProfileContactInfo;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common\ProfileContactInfoCollection;
use Klaviyo\Integration\Klaviyo\Gateway\KlaviyoGateway;
use Klaviyo\Integration\Model\Channel\GetValidChannels;
use Od\Scheduler\Model\Job\{JobHandlerInterface, JobResult, Message};
use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientCollection;
use Shopware\Core\Content\Newsletter\SalesChannel\NewsletterSubscribeRoute;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class SubscriberSyncOperation implements JobHandlerInterface
{
    public const OPERATION_HANDLER_CODE = 'od-klaviyo-subscriber-sync-handler';

    private KlaviyoGateway $klaviyoGateway;
    private ConfigurationRegistry $configurationRegistry;
    private EntityRepositoryInterface $subscriberRepository;
    private GetValidChannels $getValidChannels;

    public function __construct(
        KlaviyoGateway $klaviyoGateway,
        ConfigurationRegistry $configurationRegistry,
        EntityRepositoryInterface $subscriberRepository,
        GetValidChannels $getValidChannels
    ) {
        $this->klaviyoGateway = $klaviyoGateway;
        $this->configurationRegistry = $configurationRegistry;
        $this->subscriberRepository = $subscriberRepository;
        $this->getValidChannels = $getValidChannels;
    }

    /**
     * @param SubscriberSyncMessage $message
     * @return JobResult
     */
    public function execute(object $message): JobResult
    {
        $result = new JobResult();
        $context = $message->getContext();

        /** @var SalesChannelEntity $channel */
        foreach ($this->getValidChannels->execute($message->getContext()) as $channel) {
            try {
                $errors = $this->doOperation($message, $context, $channel);
                foreach ($errors as $error) {
                    $result->addMessage(new Message\ErrorMessage($error->getMessage()));
                }
            } catch (\Throwable $e) {
                $result->addError($e);
            }
        }

        return $result;
    }

    protected function doOperation(
        SubscriberSyncMessage $message,
        Context $context,
        SalesChannelEntity $channel
    ): ?array {
        $unsubscribedRecipients = new ProfileContactInfoCollection();
        $criteria = new Criteria();
        $criteria->addAssociation('salutation');
        $criteria->addFilter(new EqualsAnyFilter('id', $message->getSubscriberIds()));
        $criteria->addFilter(
            new EqualsAnyFilter(
                'status',
                [
                    NewsletterSubscribeRoute::STATUS_OPT_OUT,
                    NewsletterSubscribeRoute::STATUS_OPT_IN,
                    NewsletterSubscribeRoute::STATUS_DIRECT,
                ]
            )
        );
        $criteria->addFilter(new EqualsFilter('salesChannelId', $channel->getId()));

        // This limit corresponds to the maximum number of entries for some Klaviyo endpoints.
        // Change only after making sure that this will not lead to data loss
        $criteria->setLimit(100);

        /** @var NewsletterRecipientCollection $subscribersCollection */
        $subscribersCollectionIterator = new RepositoryIterator($this->subscriberRepository, $context, $criteria);

        while (($collectionPart = $subscribersCollectionIterator->fetch()) !== null) {
            $subscribersCollection = $collectionPart->getEntities();

            foreach ($subscribersCollection as $key => $recipient) {
                if (NewsletterSubscribeRoute::STATUS_OPT_OUT === $recipient->getStatus()) {
                    $unsubscribedRecipients->add(new ProfileContactInfo($recipient->getId(), $recipient->getEmail()));
                    $subscribersCollection->remove($key);
                }
            }

            if (0 !== $subscribersCollection->count() || 0 !== $unsubscribedRecipients->count()) {
                $listId = $this->configurationRegistry->getConfiguration($channel->getId())->getSubscribersListId();

                if (0 !== $subscribersCollection->count()) {
                    if (EventsProcessingOperation::REALTIME_SUBSCRIBERS_OPERATION_LABEL === $message->getJobName()) {
                        $result = $this->klaviyoGateway->subscribeToKlaviyoList(
                            $channel,
                            $subscribersCollection,
                            $listId
                        );
                    } else {
                        $result = $this->klaviyoGateway->addToKlaviyoProfilesList(
                            $channel,
                            $context,
                            $subscribersCollection,
                            $listId
                        );
                    }
                }

                if (0 !== $unsubscribedRecipients->count()) {
                    $this->klaviyoGateway->removeKlaviyoSubscribersFromList($channel, $unsubscribedRecipients, $listId);
                }
            }
        }

        return $result ?? [];
    }
}
