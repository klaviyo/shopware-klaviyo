<?php declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase\Operation\Migration;

use Klaviyo\Integration\Async\Message\Migration\ProfileExternalIdSyncMessage;
use Od\Scheduler\Model\Job\JobHandlerInterface;
use Od\Scheduler\Model\Job\JobResult;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;

class ProfileExternalIdSyncOperation implements JobHandlerInterface
{
    public const OPERATION_HANDLER_CODE = 'od-klaviyo-full-profile-external-id-sync';

    private EntityRepositoryInterface $customerRepository;

    public function __construct(EntityRepositoryInterface $customerRepository)
    {
        $this->customerRepository = $customerRepository;
    }

    /**
     * @param ProfileExternalIdSyncMessage $message
     * @return JobResult
     */
    public function execute(object $message): JobResult
    {
        $result = new JobResult();
        $context = Context::createDefaultContext();
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('email', $message->getCustomerEmails()));

        $customers = $this->customerRepository->search($criteria, $context);
        foreach ($customers as $customer) {
            // todo: search customer and update it.
        }

        return $result;
    }
}
