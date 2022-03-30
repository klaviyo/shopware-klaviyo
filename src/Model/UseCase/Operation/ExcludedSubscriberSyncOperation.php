<?php declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase\Operation;

use Od\Scheduler\Model\Job\GeneratingHandlerInterface;
use Od\Scheduler\Model\Job\{JobHandlerInterface, JobResult};
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class ExcludedSubscriberSyncOperation implements JobHandlerInterface, GeneratingHandlerInterface
{
    public const OPERATION_HANDLER_CODE = 'od-klaviyo-excluded-subscriber-sync-handler';
    private EntityRepositoryInterface $newsletterRepository;

    public function __construct(
        EntityRepositoryInterface $newsletterRepository
    ) {
        $this->newsletterRepository = $newsletterRepository;
    }

    public function execute(object $message): JobResult
    {
        //TODO message with array
        $context = Context::createDefaultContext();
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('email', $message->getEmail()));
        $subscribers = $this->newsletterRepository->search($criteria, $context);
        $subscriberData[] = [
            'id' => $subscribers->first()->getId(),
            'status' => 'optOut'
        ];
        $this->newsletterRepository->update($subscriberData, $context);
        //TODO what to return
        return new JobResult();
    }
}