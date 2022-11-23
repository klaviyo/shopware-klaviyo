<?php declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase\Operation;

use Klaviyo\Integration\Async\Message\CustomerProfileSyncMessage;
use Klaviyo\Integration\System\Tracking\Event\Customer\ProfileEventsBag;
use Klaviyo\Integration\System\Tracking\EventsTrackerInterface;
use Od\Scheduler\Model\Job\JobHandlerInterface;
use Od\Scheduler\Model\Job\JobResult;
use Od\Scheduler\Model\Job\Message\InfoMessage;
use Shopware\Core\Checkout\Customer\CustomerCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;

class CustomerProfileSyncOperation implements JobHandlerInterface
{
    public const OPERATION_HANDLER_CODE = 'od-klaviyo-customer-profile-sync-handler';

    private EventsTrackerInterface $eventsTracker;
    private EntityRepositoryInterface $customerRepository;

    public function __construct(
        EventsTrackerInterface $eventsTracker,
        EntityRepositoryInterface $customerRepository
    ) {
        $this->eventsTracker = $eventsTracker;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @param CustomerProfileSyncMessage $message
     * @return JobResult
     */
    public function execute(object $message): JobResult
    {
        $result = new JobResult();
        $result->addMessage(
            new InfoMessage(\sprintf('Total %s customer profiles to update.', \count($message->getCustomerIds())))
        );

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('id', $message->getCustomerIds()));

        /** @var CustomerCollection $customers */
        $customers = $this->customerRepository->search($criteria, $message->getContext())->getEntities();
        $this->eventsTracker->trackCustomerWritten($message->getContext(), ProfileEventsBag::fromCollection($customers));

        return new JobResult();
    }
}
