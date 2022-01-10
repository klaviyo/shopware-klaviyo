<?php declare(strict_types=1);

namespace Klaviyo\Integration\Async\MessageHandler;

use Klaviyo\Integration\Async\Message\SubscriberSyncMessage;
use Klaviyo\Integration\Model\UseCase\Operation\SubscriberSyncOperation;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\MessageQueue\Handler\AbstractMessageHandler;

class SubscriberSyncHandler extends AbstractMessageHandler
{
    private SubscriberSyncOperation $subscriberSyncOperation;
    private LoggerInterface $logger;

    public function __construct(
        SubscriberSyncOperation $subscriberSyncOperation,
        LoggerInterface $logger
    ) {
        $this->subscriberSyncOperation = $subscriberSyncOperation;
        $this->logger = $logger;
    }

    /**
     * @param SubscriberSyncMessage $message
     */
    public function handle($message): void
    {
        try {
            $this->subscriberSyncOperation->setSubscriberIds($message->getSubscriberIds());
            $this->subscriberSyncOperation->setSalesChannelId($message->getSalesChannelId());
            $this->subscriberSyncOperation->execute(Context::createDefaultContext());
        } catch (\Throwable $exception) {
            // Should not trigger any exceptions to avoid requeue
            $this->logger->error(
                sprintf('Failed to execute job[id: %s] process message', $message->getJobId()),
                [
                    'exception' => $exception
                ]
            );
        }
    }

    public static function getHandledMessages(): iterable
    {
        return [SubscriberSyncMessage::class];
    }
}
