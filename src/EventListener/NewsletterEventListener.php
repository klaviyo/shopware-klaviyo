<?php

namespace Klaviyo\Integration\EventListener;

use Shopware\Core\Content\Newsletter\Event\NewsletterConfirmEvent;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NewsletterEventListener implements EventSubscriberInterface
{
    protected EntityRepositoryInterface $newsletterSubscriber;

    public function __construct(EntityRepositoryInterface $newsletterSubscriber)
    {
        $this->newsletterSubscriber = $newsletterSubscriber;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            NewsletterConfirmEvent::class => 'onNewsletterConfirm'
        ];
    }

    public function onNewsletterConfirm(NewsletterConfirmEvent $event)
    {
        $id = Uuid::randomHex();
        $this->newsletterSubscriber->create([
            [
                'id' => $id,
                'email' => $event->getNewsletterRecipient()->getEmail(),
                'created_at' => $event->getNewsletterRecipient()->getCreatedAt()
            ]

        ], $event->getContext());

        setcookie('klaviyo_subscriber', $id);
    }
}