<?php declare(strict_types=1);

namespace Klaviyo\Integration\Async\MessageHandler;

use Klaviyo\Integration\Model\UseCase\Operation\OrderEventsSyncOperation;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\MessageQueue\Handler\AbstractMessageHandler;
use Klaviyo\Integration\Async\Message\OrderEventSyncMessage;

class OrderEventsSyncHandler extends AbstractMessageHandler
{
    private OrderEventsSyncOperation $syncOperation;
    private LoggerInterface $logger;

    public function __construct(
        OrderEventsSyncOperation $syncOperation,
        LoggerInterface $logger
    ) {
        $this->syncOperation = $syncOperation;
        $this->logger = $logger;
    }

    /**
     * @param OrderEventSyncMessage $message
     */
    public function handle($message): void
    {
        try {
            $this->syncOperation->setEventIds($message->getEventIds());
            $this->syncOperation->execute(Context::createDefaultContext());
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
        return [OrderEventSyncMessage::class];
    }
}
