<?php declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase\Operation;

use Klaviyo\Integration\Configuration\ConfigurationRegistry;
use Klaviyo\Integration\Klaviyo\Gateway\KlaviyoGateway;
use Klaviyo\Integration\System\OperationResult;
use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class SubscriberSyncOperation
{
    /**
     * @var string[]
     */
    private array $subscriberIds;
    private string $salesChannelId;
    private KlaviyoGateway $klaviyoGateway;
    private ConfigurationRegistry $configurationRegistry;
    private EntityRepositoryInterface $subscriberRepository;
    private EntityRepositoryInterface $salesChannelRepository;

    public function __construct(
        KlaviyoGateway $klaviyoGateway,
        ConfigurationRegistry $configurationRegistry,
        EntityRepositoryInterface $subscriberRepository,
        EntityRepositoryInterface $salesChannelRepository
    ) {
        $this->klaviyoGateway = $klaviyoGateway;
        $this->configurationRegistry = $configurationRegistry;
        $this->subscriberRepository = $subscriberRepository;
        $this->salesChannelRepository = $salesChannelRepository;
    }

    public function setSubscriberIds(array $subscriberIds): void
    {
        $this->subscriberIds = $subscriberIds;
    }

    public function setSalesChannelId(string $salesChannelId): void
    {
        $this->salesChannelId = $salesChannelId;
    }

    public function execute(Context $context): OperationResult
    {
        $result = new OperationResult();
        /** @var SalesChannelEntity $channel */
        $channel = $this->salesChannelRepository->search(new Criteria([$this->salesChannelId]), $context)->first();

        if ($channel !== null) {
            try {
                $this->doOperation($context, $channel);
            } catch (\Exception $e) {
                throw $e;
            }
        }

        return $result;
    }

    protected function doOperation(Context $context, SalesChannelEntity $channel)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('id', $this->subscriberIds));
        /** @var NewsletterRecipientCollection $subscribersCollection */
        $subscribersCollection = $this->subscriberRepository->search($criteria, $context)->getEntities();

        $listId = $this->klaviyoGateway->getListIdByListName(
            $channel,
            $this->configurationRegistry->getConfiguration($channel)->getSubscribersListName()
        );

        $this->klaviyoGateway->addToKlaviyoProfilesList($channel, $subscribersCollection, $listId);
    }
}
