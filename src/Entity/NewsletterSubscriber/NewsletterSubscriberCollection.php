<?php declare(strict_types=1);

namespace Klaviyo\Integration\Entity\NewsletterSubscriber;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class NewsletterSubscriberCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return NewsletterSubscriberEntity::class;
    }
}