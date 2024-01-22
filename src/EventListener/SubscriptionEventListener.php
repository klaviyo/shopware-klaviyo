<?php

declare(strict_types=1);

namespace Klaviyo\Integration\EventListener;

use Klaviyo\Integration\Model\Channel\GetValidChannelConfig;
use Klaviyo\Integration\System\Tracking\EventsTrackerInterface;
use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientEntity;
use Shopware\Core\Content\Newsletter\Event;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Psr\Log\LoggerInterface;

class SubscriptionEventListener implements EventSubscriberInterface
{
    private EntityRepositoryInterface $eventsRepository;
    private GetValidChannelConfig $getValidChannelConfig;
    private LoggerInterface $logger;

    public function __construct(
        EntityRepositoryInterface $eventsRepository,
        GetValidChannelConfig $getValidChannelConfig,
        LoggerInterface $logger
    ) {
        $this->eventsRepository = $eventsRepository;
        $this->getValidChannelConfig = $getValidChannelConfig;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Event\NewsletterConfirmEvent::class => 'onUserSubscription',
            Event\NewsletterUnsubscribeEvent::class => 'onUserUnsubscription'
        ];
    }

    public function onUserSubscription(Event\NewsletterConfirmEvent $event): void
    {
        // TODO: add feature to disable newsletter opt_(in/out) tracking
        if ($this->getValidChannelConfig->execute($event->getSalesChannelId()) === null) {
            return;
        }

        try {
            $recipient = $event->getNewsletterRecipient();
            $event->getContext()->addExtension('klaviyo_subscriber_id', new ArrayStruct([$recipient->getId()]));
            $this->writeRecipientEvent(
                $event->getContext(),
                $event->getNewsletterRecipient(),
                EventsTrackerInterface::SUBSCRIBER_EVENT_SUB
            );
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
        }
    }

    public function onUserUnsubscription(Event\NewsletterUnsubscribeEvent $event): void
    {
        if ($this->getValidChannelConfig->execute($event->getSalesChannelId()) === null) {
            return;
        }

        try {
            $this->writeRecipientEvent(
                $event->getContext(),
                $event->getNewsletterRecipient(),
                EventsTrackerInterface::SUBSCRIBER_EVENT_UNSUB
            );
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
        }
    }

    private function writeRecipientEvent(Context $context, NewsletterRecipientEntity $recipient, string $type): void
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
}
