<?php declare(strict_types=1);

namespace Klaviyo\Integration\Entity\Helper;

use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientEntity;
use Shopware\Core\Content\Newsletter\SalesChannel\NewsletterSubscribeRoute;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class NewsletterSubscriberHelper
{
    private EntityRepositoryInterface $subscriberRepository;

    public function __construct(EntityRepositoryInterface $subscriberRepository)
    {
        $this->subscriberRepository = $subscriberRepository;
    }

    public function getSubscriber(string $id, Context $context): ?NewsletterRecipientEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $id));
        $allowedStatuses = [NewsletterSubscribeRoute::STATUS_OPT_IN, NewsletterSubscribeRoute::STATUS_DIRECT];
        /** @var NewsletterRecipientEntity $recipient */
        $recipient = $this->subscriberRepository->search($criteria, $context)->first();

        if ($recipient && in_array($recipient->getStatus(), $allowedStatuses)) {
            return $recipient;
        }

        return null;
    }
}
