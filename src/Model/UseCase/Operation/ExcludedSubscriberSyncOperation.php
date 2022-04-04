<?php declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase\Operation;

use Od\Scheduler\Model\Job\GeneratingHandlerInterface;
use Od\Scheduler\Model\Job\{JobHandlerInterface, JobResult};
use Shopware\Core\Content\Newsletter\SalesChannel\NewsletterSubscribeRoute;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;

class ExcludedSubscriberSyncOperation implements JobHandlerInterface, GeneratingHandlerInterface
{
    public const OPERATION_HANDLER_CODE = 'od-klaviyo-excluded-subscriber-sync-handler';

    private EntityRepositoryInterface $newsletterRepository;

    public function __construct(EntityRepositoryInterface $newsletterRepository)
    {
        $this->newsletterRepository = $newsletterRepository;
    }

    public function execute(object $message): JobResult
    {
        $context = Context::createDefaultContext();
        $criteria = new Criteria();
        $emails = array_map(function ($email) {
            return $email->getEmail();
        }, $message->getEmails());
        $criteria->addFilter(new EqualsAnyFilter('email', $emails));
        $subscribers = $this->newsletterRepository->search($criteria, $context);
        $subscriberData = [];
        foreach ($subscribers as $subscriber) {
            $subscriberData[] = [
                'id' => $subscriber->getId(),
                'email' => $subscriber->getEmail(),
                'status' => NewsletterSubscribeRoute::STATUS_OPT_OUT
            ];
        }
        $this->newsletterRepository->update($subscriberData, $context);

        return new JobResult();
    }
}