<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Gateway\Translator;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common\ProfileContactInfo;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common\ProfileContactInfoCollection;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\SubscribeCustomersToList\RealSubscribersToKlaviyoRequest;
use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientCollection;
use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientEntity;

class RealSubscribersToKlaviyoRequestsTranslator
{
    /**
     * @param NewsletterRecipientCollection $collection
     * @param string $listId
     * @return RealSubscribersToKlaviyoRequest
     */
    public function translateToSubscribeRequest(
        NewsletterRecipientCollection $collection,
        string $listId
    ): RealSubscribersToKlaviyoRequest {
        $profiles = $this->translateToProfilesList($collection);

        return new RealSubscribersToKlaviyoRequest($listId, $profiles);
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
            $profiles->add(new ProfileContactInfo($recipientEntity->getId(), $recipientEntity->getEmail()));
        }

        return $profiles;
    }
}
