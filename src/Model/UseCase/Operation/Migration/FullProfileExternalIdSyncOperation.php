<?php declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase\Operation\Migration;

use Klaviyo\Integration\Async\Message\Migration\{FullProfileExternalIdSyncMessage, ProfileExternalIdSyncMessage};
use Klaviyo\Integration\Model\Channel\ChannelRepositoryWithValidConfig;
use Od\Scheduler\Model\Job\GeneratingHandlerInterface;
use Od\Scheduler\Model\Job\JobHandlerInterface;
use Od\Scheduler\Model\Job\JobResult;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Symfony\Component\Messenger\MessageBusInterface;

class FullProfileExternalIdSyncOperation implements JobHandlerInterface, GeneratingHandlerInterface
{
    public const OPERATION_HANDLER_CODE = 'od-klaviyo-full-profile-external-id-sync';

    private EntityRepositoryInterface $customerRepository;
    private ChannelRepositoryWithValidConfig $channelRepoWithValidConfig;
    private MessageBusInterface $messageBus;

    public function __construct(
        EntityRepositoryInterface $customerRepository,
        ChannelRepositoryWithValidConfig $channelRepoWithValidConfig,
        MessageBusInterface $messageBus
    ) {
        $this->customerRepository = $customerRepository;
        $this->channelRepoWithValidConfig = $channelRepoWithValidConfig;
        $this->messageBus = $messageBus;
    }

    /**
     * @param FullProfileExternalIdSyncMessage $message
     * @return JobResult
     */
    public function execute(object $message): JobResult
    {
        $result = new JobResult();
        $context = Context::createDefaultContext();

        /** @var SalesChannelEntity $channel */
        foreach ($this->channelRepoWithValidConfig->get() as $channel) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('salesChannelId', $channel->getId()));
            $criteria->setLimit(100);
            $iterator = new RepositoryIterator($this->customerRepository, $context, $criteria);

            while (($customers = $iterator->fetch()) !== null) {
                $customerEmails = array_values(array_map(function (CustomerEntity $customer) {
                    return $customer->getEmail();
                }, $customers->getElements()));
                $jobMessage = new ProfileExternalIdSyncMessage(Uuid::randomHex(), $channel->getId(), $customerEmails);
                $this->messageBus->dispatch($jobMessage);
            }
        }

        return $result;
    }
}
