<?php declare(strict_types=1);

namespace Klaviyo\Integration\EventListener;

use Klaviyo\Integration\System\Tracking\Event\Customer\ProfileEventsBag;
use Klaviyo\Integration\System\Tracking\EventsTrackerInterface;
use Shopware\Core\Checkout\Customer\CustomerCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Checkout\Customer\CustomerEvents;

class CustomerWrittenEventListener implements EventSubscriberInterface
{
    private EventsTrackerInterface $eventsTracker;
    private EntityRepositoryInterface $customerRepository;

    public function __construct(
        EventsTrackerInterface $eventsTracker,
        EntityRepositoryInterface $customerRepository
    ) {
        $this->eventsTracker = $eventsTracker;
        $this->customerRepository = $customerRepository;
    }

    public static function getSubscribedEvents()
    {
        return [
            CustomerEvents::CUSTOMER_WRITTEN_EVENT => 'onCustomerWritten'
        ];
    }

    public function onCustomerWritten(EntityWrittenEvent $event)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('id', $event->getIds()));

        /** @var CustomerCollection $customers */
        $customers = $this->customerRepository->search($criteria, $event->getContext())->getEntities();
        $this->eventsTracker->trackCustomerWritten($event->getContext(), ProfileEventsBag::fromCollection($customers));
    }
}
