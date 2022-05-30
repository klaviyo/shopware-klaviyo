<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Gateway\Translator;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\Common\CustomerProperties;
use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientEntity;

class NewsletterSubscriberPropertiesTranslator
{
    public function translateSubscriber(NewsletterRecipientEntity $subscriberEntity): CustomerProperties
    {
        return new CustomerProperties(
            $subscriberEntity->getEmail(),
            null,
            $subscriberEntity->getFirstName() ?? '',
            $subscriberEntity->getLastName() ?? ''
        );
    }
}
