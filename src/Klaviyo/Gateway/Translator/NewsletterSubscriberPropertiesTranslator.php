<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Gateway\Translator;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\Common\CustomerProperties;
use Klaviyo\Integration\Utils\LocaleCodeProducer;
use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientEntity;
use Shopware\Core\Framework\Context;

class NewsletterSubscriberPropertiesTranslator
{
    private LocaleCodeProducer $localeCodeProducer;

    public function __construct(LocaleCodeProducer $localeCodeProducer)
    {
        $this->localeCodeProducer = $localeCodeProducer;
    }

    public function translateSubscriber(Context $context, NewsletterRecipientEntity $subscriberEntity): CustomerProperties
    {
        $localeCode = $this->localeCodeProducer->getOptionalLocaleCodeForLanguage(
            $subscriberEntity->getLanguageId(),
            $context
        );

        return new CustomerProperties(
            $subscriberEntity->getEmail(),
            null,
            $subscriberEntity->getFirstName() ?? '',
            $subscriberEntity->getLastName() ?? '',
            null,
            null,
            null,
            null,
            null,
            null,
            [],
            null,
            null,
            null,
            null,
            null,
            $localeCode
        );
    }
}
