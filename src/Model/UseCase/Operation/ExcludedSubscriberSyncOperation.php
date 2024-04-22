<?php declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase\Operation;

use Klaviyo\Integration\Async\Message\ExcludedSubscriberSyncMessage;
use Od\Scheduler\Model\Job\{JobHandlerInterface, JobResult, Message\InfoMessage};
use Shopware\Core\Content\Newsletter\SalesChannel\NewsletterSubscribeRoute;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\{EqualsAnyFilter, EqualsFilter};

class ExcludedSubscriberSyncOperation implements JobHandlerInterface
{
    public const OPERATION_HANDLER_CODE = 'od-klaviyo-excluded-subscriber-sync-handler';

    /**
     * @var EntityRepositoryInterface
     */
    private EntityRepositoryInterface $newsletterRepository;

    /**
     * @param EntityRepositoryInterface $newsletterRepository
     */
    public function __construct(EntityRepositoryInterface $newsletterRepository)
    {
        $this->newsletterRepository = $newsletterRepository;
    }

    /**
     * @param ExcludedSubscriberSyncMessage $message
     *
     * @return JobResult
     */
    public function execute(object $message): JobResult
    {
        $result = new JobResult();
        $context = $message->getContext();
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('email', $message->getEmails()));
        $criteria->addFilter(new EqualsFilter('salesChannelId', $message->getSalesChannelId()));
        $subscribers = $this->newsletterRepository->search($criteria, $context);

        if (count($message->getEmails())) {
            $result->addMessage(
                new InfoMessage(
                    \sprintf('Total %s customers was unsubscribed.', \count($message->getEmails()))
                )
            );
        }

        if (!empty($message->getEmails()) && count($message->getEmails())) {
            $result->addMessage(new InfoMessage(
                \sprintf(
                    'Channel[id: %s] awaiting deletion Newsletter emails: %s',
                    $message->getSalesChannelId(),
                    \implode(',', $message->getEmails())
                )
            ));
        }

        $subscriberData = array_values(array_map(function ($subscriber) {
            return [
                'id' => $subscriber->getId(),
                'email' => $subscriber->getEmail(),
                'status' => NewsletterSubscribeRoute::STATUS_OPT_OUT
            ];
        }, $subscribers->getElements()));
        $this->newsletterRepository->update($subscriberData, $context);

        return $result;
    }
}
