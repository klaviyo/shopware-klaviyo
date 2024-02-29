<?php

namespace Klaviyo\Integration\Klaviyo\Gateway\Translator;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\AddMembersToList\AddProfilesToListRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common\ProfileContactInfo;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common\ProfileContactInfoCollection;
use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientCollection;
use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientEntity;

class SubscribersToKlaviyoRequestsTranslator
{
    public function translateToAddProfilesRequest(
        NewsletterRecipientCollection $collection,
        string $listId
    ): AddProfilesToListRequest {
        $profiles = $this->translateToProfilesList($collection);

        return new AddProfilesToListRequest($listId, $profiles);
    }

    private function translateToProfilesList(NewsletterRecipientCollection $collection): ProfileContactInfoCollection
    {
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
                $salutation
            ));
        }

        return $profiles;
    }
}
