<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Gateway\Translator;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common\ProfileContactInfo;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common\ProfileContactInfoCollection;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\SubscribeCustomersToList\RealSubscribersToKlaviyoRequest;
use Klaviyo\Integration\Utils\LocaleCodeProducer;
use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientCollection;
use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientEntity;
use Shopware\Core\Framework\Context;

class RealSubscribersToKlaviyoRequestsTranslator
{
    private LocaleCodeProducer $localeCodeProducer;

    public function __construct(LocaleCodeProducer $localeCodeProducer)
    {
        $this->localeCodeProducer = $localeCodeProducer;
    }

    /**
     * @param NewsletterRecipientCollection $collection
     * @param string $listId
     * @return RealSubscribersToKlaviyoRequest
     */
    public function translateToSubscribeRequest(
        Context $context,
        NewsletterRecipientCollection $collection,
        string $listId
    ): RealSubscribersToKlaviyoRequest {
        $profiles = $this->translateToProfilesList($context, $collection);

        return new RealSubscribersToKlaviyoRequest($listId, $profiles);
    }

    /**
     * @param NewsletterRecipientCollection $collection
     * @return ProfileContactInfoCollection
     */
    private function translateToProfilesList(
        Context $context,
        NewsletterRecipientCollection $collection
    ): ProfileContactInfoCollection {
        $profiles = new ProfileContactInfoCollection();
        /** @var NewsletterRecipientEntity $recipientEntity */
        foreach ($collection as $recipientEntity) {
            $profiles->add(new ProfileContactInfo(
                $recipientEntity->getId(),
                $recipientEntity->getEmail(),
                null,
                null,
                null,
                null,
                $this->resolveLocaleCode($context, $recipientEntity)
            ));
        }

        return $profiles;
    }

    private function resolveLocaleCode(Context $context, NewsletterRecipientEntity $recipientEntity): ?string
    {
        try {
            $code = $this->localeCodeProducer->getLocaleCodeFromContext(
                $recipientEntity->getLanguageId(),
                $context
            );

            return $code !== '' ? $code : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
