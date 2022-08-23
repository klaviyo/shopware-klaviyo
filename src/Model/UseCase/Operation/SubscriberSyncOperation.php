<?php declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase\Operation;

use Klaviyo\Integration\Async\Message\SubscriberSyncMessage;
use Klaviyo\Integration\Configuration\ConfigurationRegistry;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common\ProfileContactInfo;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common\ProfileContactInfoCollection;
use Klaviyo\Integration\Klaviyo\Gateway\GetListIdByListNameInterface;
use Klaviyo\Integration\Klaviyo\Gateway\KlaviyoGateway;
use Klaviyo\Integration\Model\Channel\GetValidChannels;
use Od\Scheduler\Model\Job\{JobHandlerInterface, JobResult, Message};
use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientCollection;
use Shopware\Core\Content\Newsletter\SalesChannel\NewsletterSubscribeRoute;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
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
    private GetListIdByListNameInterface $listIdByListName;
    private GetValidChannels $getValidChannels;

    public function __construct(
        KlaviyoGateway $klaviyoGateway,
        ConfigurationRegistry $configurationRegistry,
        EntityRepositoryInterface $subscriberRepository,
        GetListIdByListNameInterface $listIdByListName,
        GetValidChannels $getValidChannels
    ) {
        $this->klaviyoGateway = $klaviyoGateway;
        $this->configurationRegistry = $configurationRegistry;
        $this->subscriberRepository = $subscriberRepository;
        $this->listIdByListName = $listIdByListName;
        $this->getValidChannels = $getValidChannels;
    }

    /**
     * @param SubscriberSyncMessage $message
     * @return JobResult
     */
    public function execute(object $message): JobResult
    {
        $result = new JobResult();
        $context = Context::createDefaultContext();

        /** @var SalesChannelEntity $channel */
        foreach ($this->getValidChannels->execute() as $channel) {
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

    protected function doOperation(SubscriberSyncMessage $message, Context $context, SalesChannelEntity $channel): ?array
    {
        $unsubscribedRecipients = new ProfileContactInfoCollection();
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('id', $message->getSubscriberIds()));
        $criteria->addFilter(
            new EqualsAnyFilter(
                'status',
                [
                    NewsletterSubscribeRoute::STATUS_OPT_OUT,
                    NewsletterSubscribeRoute::STATUS_OPT_IN,
                    NewsletterSubscribeRoute::STATUS_DIRECT
                ]
            )
        );
        $criteria->addFilter(new EqualsFilter('salesChannelId', $channel->getId()));
        /** @var NewsletterRecipientCollection $subscribersCollection */
        $subscribersCollection = $this->subscriberRepository->search($criteria, $context)->getEntities();

        foreach ($subscribersCollection as $key => $recipient) {
            if ($recipient->getStatus() === NewsletterSubscribeRoute::STATUS_OPT_OUT) {
                $unsubscribedRecipients->add(new ProfileContactInfo($recipient->getEmail()));
                $subscribersCollection->remove($key);
            }
        }

        if ($subscribersCollection->count() !== 0 || $unsubscribedRecipients->count() !== 0) {
            $listId = $this->listIdByListName->execute(
                $channel,
                $this->configurationRegistry->getConfiguration($channel->getId())->getSubscribersListName()
            );

            if ($subscribersCollection->count() !== 0) {
                $result = $this->klaviyoGateway->addToKlaviyoProfilesList($channel, $subscribersCollection, $listId);
            }

            if ($unsubscribedRecipients->count() !== 0) {
                $this->klaviyoGateway->removeKlaviyoSubscribersFromList($channel, $unsubscribedRecipients, $listId);
            }
        }

        return $result ?? [];
    }
}
