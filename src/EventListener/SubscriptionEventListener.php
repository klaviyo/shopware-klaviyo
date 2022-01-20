<?php declare(strict_types=1);

namespace Klaviyo\Integration\EventListener;

use Klaviyo\Integration\System\Tracking\EventsTrackerInterface;
use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientEntity;
use Shopware\Core\Content\Newsletter\Event\NewsletterConfirmEvent;
use Shopware\Core\Content\Newsletter\Event\NewsletterUnsubscribeEvent;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SubscriptionEventListener implements EventSubscriberInterface
{
    private EntityRepositoryInterface $eventsRepository;

    public function __construct(EntityRepositoryInterface $eventsRepository)
    {
        $this->eventsRepository = $eventsRepository;
    }

    public function onUserSubscription(NewsletterConfirmEvent $event)
    {
        try {
            $this->writeRecipientEvent(
                $event->getContext(),
                $event->getNewsletterRecipient(),
                EventsTrackerInterface::SUBSCRIBER_EVENT_SUB
            );
        } catch (\Throwable $e) {
            null;
        }
    }

    public function onUserUnsubscription(NewsletterUnsubscribeEvent $event)
    {
        try {
            $this->writeRecipientEvent(
                $event->getContext(),
                $event->getNewsletterRecipient(),
                EventsTrackerInterface::SUBSCRIBER_EVENT_UNSUB
            );
        } catch (\Throwable $e) {
            null;
        }
    }

    private function writeRecipientEvent(Context $context, NewsletterRecipientEntity $recipient, string $type)
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->eventsRepository->upsert([
            [
                'id' => Uuid::randomHex(),
                'type' => $type,
                'entityId' => $recipient->getId(),
                'salesChannelId' => $recipient->getSalesChannelId(),
                'happenedAt' => $now
            ]
        ], $context);
    }

    public static function getSubscribedEvents()
    {
        return [
            NewsletterConfirmEvent::class => 'onUserSubscription',
            NewsletterUnsubscribeEvent::class => 'onUserUnsubscription'
        ];
    }
}
