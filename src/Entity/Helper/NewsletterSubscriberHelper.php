<?php declare(strict_types=1);

namespace Klaviyo\Integration\Entity\Helper;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class NewsletterSubscriberHelper
{
    private EntityRepositoryInterface $klaviyoSubscriberRepository;
    private EntityRepositoryInterface $subscriberRepository;

    public function __construct(
        EntityRepositoryInterface $klaviyoSubscriberRepository,
        EntityRepositoryInterface $subscriberRepository
    ) {
        $this->klaviyoSubscriberRepository = $klaviyoSubscriberRepository;
        $this->subscriberRepository = $subscriberRepository;
    }

    public function getSubscriber(string $id, Context $context)
    {
        $klaviyoSubscriber = $this->getSubscriberFromKlaviyoSubscriberRepository($id, $context);
        $email = $klaviyoSubscriber->getEmail();
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('email', $email));

        return $this->subscriberRepository->search($criteria, $context)->first();
    }

    private function getSubscriberFromKlaviyoSubscriberRepository(string $id, Context $context)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $id));

        return $this->klaviyoSubscriberRepository->search($criteria, $context)->getEntities()->first();
    }
}