<?php declare(strict_types=1);

namespace Klaviyo\Integration\Async\MessageHandler;

use Klaviyo\Integration\Async\Message\OrderSyncMessage;
use Klaviyo\Integration\Model\UseCase\Operation\OrderSyncOperation;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\MessageQueue\Handler\AbstractMessageHandler;

class OrderSyncHandler extends AbstractMessageHandler
{
    private OrderSyncOperation $orderSyncOperation;
    private LoggerInterface $logger;

    public function __construct(
        OrderSyncOperation $orderSyncOperation,
        LoggerInterface $logger
    ) {
        $this->orderSyncOperation = $orderSyncOperation;
        $this->logger = $logger;
    }

    /**
     * @param OrderSyncMessage $message
     */
    public function handle($message): void
    {
        try {
            $this->orderSyncOperation->setOrderIds($message->getOrderIds());
            $this->orderSyncOperation->execute(Context::createDefaultContext());
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
        return [OrderSyncMessage::class];
    }
}
