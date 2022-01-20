<?php declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase\Operation;

use Klaviyo\Integration\Async\Message\CartEventSyncMessage;
use Klaviyo\Integration\Entity\CartRequest\CartRequestEntity;
use Klaviyo\Integration\System\Tracking\Event\Cart\CartEventRequestBag;
use Klaviyo\Integration\System\Tracking\EventsTrackerInterface;
use Od\Scheduler\Model\Job\GeneratingHandlerInterface;
use Od\Scheduler\Model\Job\JobHandlerInterface;
use Od\Scheduler\Model\Job\JobResult;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;

class CartEventSyncOperation implements JobHandlerInterface, GeneratingHandlerInterface
{
    public const OPERATION_HANDLER_CODE = 'od-klaviyo-cart-event-sync-handler';

    private EventsTrackerInterface $eventsTracker;
    private EntityRepositoryInterface $cartEventRequestRepository;

    public function __construct(
        EventsTrackerInterface $eventsTracker,
        EntityRepositoryInterface $cartEventRequestRepository
    ) {
        $this->eventsTracker = $eventsTracker;
        $this->cartEventRequestRepository = $cartEventRequestRepository;
    }

    /**
     * @param CartEventSyncMessage $message
     * @return JobResult
     */
    public function execute(object $message): JobResult
    {
        $result = new JobResult();
        $context = Context::createDefaultContext();
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('id', $message->getEventRequestIds()));
        $cartEvents = $this->cartEventRequestRepository->search($criteria, $context);
        $requestBag = new CartEventRequestBag();

        /** @var CartRequestEntity $cartEvent */
        foreach ($cartEvents as $cartEvent) {
            try {
                $requestBag->add(
                    unserialize(base64_decode($cartEvent->getSerializedRequest())),
                    $cartEvent->getSalesChannelId()
                );
            } catch (\Throwable $e) {
                $result->addError($e);
                continue;
            }
        }

        //TODO: add result
        $this->eventsTracker->trackAddedToCart($context, $requestBag);
        $deleteDataSet = array_map(function ($id) {
            return ['id' => $id];
        }, $message->getEventRequestIds());
        $this->cartEventRequestRepository->delete($deleteDataSet, $context);

        return $result;
    }
}
