<?php

namespace Klaviyo\Integration\Klaviyo\Gateway\Translator;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\AddMembersToList\AddProfilesToListRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common\ProfileContactInfo;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common\ProfileContactInfoCollection;
use Klaviyo\Integration\Utils\LocaleCodeProducer;
use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientCollection;
use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientEntity;
use Shopware\Core\Framework\Context;

class SubscribersToKlaviyoRequestsTranslator
{
    private LocaleCodeProducer $localeCodeProducer;

    public function __construct(LocaleCodeProducer $localeCodeProducer)
    {
        $this->localeCodeProducer = $localeCodeProducer;
    }

    public function translateToAddProfilesRequest(
        Context $context,
        NewsletterRecipientCollection $collection,
        string $listId
    ): AddProfilesToListRequest {
        $profiles = $this->translateToProfilesList($context, $collection);

        return new AddProfilesToListRequest($listId, $profiles);
    }

    private function translateToProfilesList(
        Context $context,
        NewsletterRecipientCollection $collection
    ): ProfileContactInfoCollection {
        $profiles = new ProfileContactInfoCollection();
        /** @var NewsletterRecipientEntity $recipientEntity */
        foreach ($collection as $recipientEntity) {
            $salutation = null;

            if ($recipientEntity->getSalutation()) {
                $salutation = $recipientEntity->getSalutation()->getDisplayName();
            }

            $profiles->add(new ProfileContactInfo(
                $recipientEntity->getId(),
                $recipientEntity->getEmail(),
                $recipientEntity->getFirstName(),
                $recipientEntity->getLastName(),
                $salutation,
                $recipientEntity->getCreatedAt(),
                $this->localeCodeProducer->getOptionalLocaleCodeForLanguage(
                    $recipientEntity->getLanguageId(),
                    $context
                )
            ));
        }

        return $profiles;
    }
}
