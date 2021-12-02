<?php

namespace Klaviyo\Integration\Async;

use Klaviyo\Integration\Job\JobExecutor;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\MessageQueue\Handler\AbstractMessageHandler;

class JobExecutionMessageHandler extends AbstractMessageHandler
{
    private JobExecutor $jobExecutor;
    private LoggerInterface $logger;

    public function __construct(JobExecutor $jobExecutor, LoggerInterface $logger)
    {
        $this->jobExecutor = $jobExecutor;
        $this->logger = $logger;
    }

    public function handle($message): void
    {
        try {
            if (!$message instanceof JobExecutionMessage) {
                throw new \RuntimeException(
                    sprintf(
                        'Unexpected message type, expected %s, got %s',
                        JobExecutionMessage::class,
                        is_object($message) ? get_class($message) : gettype($message)
                    )
                );
            }

            $context = Context::createDefaultContext();
            $this->jobExecutor
                ->executeJob($context, $message->getJobId());
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
        return [JobExecutionMessage::class];
    }
}