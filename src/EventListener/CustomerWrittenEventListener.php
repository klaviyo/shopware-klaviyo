<?php

declare(strict_types=1);

namespace Klaviyo\Integration\EventListener;

use Klaviyo\Integration\Model\Channel\GetValidChannelConfig;
use Klaviyo\Integration\System\Tracking\Event\Customer\ProfileEventsBag;
use Klaviyo\Integration\System\Tracking\EventsTrackerInterface;
use Shopware\Core\Checkout\Customer\CustomerCollection;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Checkout\Customer\CustomerEvents;

class CustomerWrittenEventListener implements EventSubscriberInterface
{
    private EventsTrackerInterface $eventsTracker;
    private EntityRepository $customerRepository;
    private GetValidChannelConfig $getValidChannelConfig;

    public function __construct(
        EventsTrackerInterface $eventsTracker,
        EntityRepository $customerRepository,
        GetValidChannelConfig $getValidChannelConfig
    ) {
        $this->eventsTracker = $eventsTracker;
        $this->customerRepository = $customerRepository;
        $this->getValidChannelConfig = $getValidChannelConfig;
    }

    public static function getSubscribedEvents()
    {
        return [
            CustomerEvents::CUSTOMER_WRITTEN_EVENT => 'onCustomerWritten'
        ];
    }

    public function onCustomerWritten(EntityWrittenEvent $event)
    {
        $allowedChannelIds = [];
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('id', $event->getIds()));

        /** @var CustomerCollection $customers */
        $customers = $this->customerRepository->search($criteria, $event->getContext())->getEntities();
        $channelIds = $customers->map(fn(CustomerEntity $customer) => $customer->getSalesChannelId());
        $channelIds = \array_unique(\array_values($channelIds));

        foreach ($channelIds as $channelId) {
            if ($this->getValidChannelConfig->execute($channelId) !== null) {
                $allowedChannelIds[] = $channelId;
            }
        }

        $customers = $customers->filter(function (CustomerEntity $customer) use ($allowedChannelIds) {
            return \in_array($customer->getSalesChannelId(), $allowedChannelIds);
        });

        $this->eventsTracker->trackCustomerWritten($event->getContext(), ProfileEventsBag::fromCollection($customers));
    }
}
