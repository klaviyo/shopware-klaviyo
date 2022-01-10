<?php declare(strict_types=1);

namespace Klaviyo\Integration\Async\MessageHandler;

use Klaviyo\Integration\Model\UseCase\Operation\FullSubscriberSyncOperation;
use Klaviyo\Integration\Async\Message\FullSubscriberSyncMessage;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\MessageQueue\Handler\AbstractMessageHandler;

class FullSubscriberSyncHandler extends AbstractMessageHandler
{
    private FullSubscriberSyncOperation $fullSubscriberSyncOperation;
    private LoggerInterface $logger;

    public function __construct(
        FullSubscriberSyncOperation $fullSubscriberSyncOperation,
        LoggerInterface $logger
    ) {
        $this->fullSubscriberSyncOperation = $fullSubscriberSyncOperation;
        $this->logger = $logger;
    }

    /**
     * @param FullSubscriberSyncMessage $message
     */
    public function handle($message): void
    {
        try {
            $this->fullSubscriberSyncOperation->setParentJobId($message->getJobId());
            $this->fullSubscriberSyncOperation->execute(Context::createDefaultContext());
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
        return [FullSubscriberSyncMessage::class];
    }
}
