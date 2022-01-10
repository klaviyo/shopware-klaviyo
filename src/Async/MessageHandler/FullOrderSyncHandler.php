<?php declare(strict_types=1);

namespace Klaviyo\Integration\Async\MessageHandler;

use Klaviyo\Integration\Async\Message\FullOrderSyncMessage;
use Klaviyo\Integration\Model\UseCase\Operation\FullOrderSyncOperation;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\MessageQueue\Handler\AbstractMessageHandler;

class FullOrderSyncHandler extends AbstractMessageHandler
{
    private FullOrderSyncOperation $fullOrderSyncOperation;
    private LoggerInterface $logger;

    public function __construct(
        FullOrderSyncOperation $fullOrderSyncOperation,
        LoggerInterface $logger
    ) {
        $this->fullOrderSyncOperation = $fullOrderSyncOperation;
        $this->logger = $logger;
    }

    /**
     * @param FullOrderSyncMessage $message
     */
    public function handle($message): void
    {
        try {
            $this->fullOrderSyncOperation->setParentJobId($message->getJobId());
            $this->fullOrderSyncOperation->execute(Context::createDefaultContext());
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
        return [FullOrderSyncMessage::class];
    }
}
