<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Gateway\Translator;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common\ProfileContactInfo;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common\ProfileContactInfoCollection;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\SubscribeCustomersToList\SubscribeToListRequest;
use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientCollection;
use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientEntity;

class RealSubscribersToKlaviyoRequestsTranslator
{
    /**
     * @param NewsletterRecipientCollection $collection
     * @param string $listId
     * @return SubscribeToListRequest
     */
    public function translateToSubscribeRequest(
        NewsletterRecipientCollection $collection,
        string $listId
    ): SubscribeToListRequest {
        $profiles = $this->translateToProfilesList($collection);

        return new SubscribeToListRequest($listId, $profiles);
    }

    /**
     * @param NewsletterRecipientCollection $collection
     * @return ProfileContactInfoCollection
     */
    private function translateToProfilesList(NewsletterRecipientCollection $collection): ProfileContactInfoCollection
    {
        $profiles = new ProfileContactInfoCollection();
        /** @var NewsletterRecipientEntity $recipientEntity */
        foreach ($collection as $recipientEntity) {
            $profiles->add(new ProfileContactInfo($recipientEntity->getEmail()));
        }

        return $profiles;
    }
}
