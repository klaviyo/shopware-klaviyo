<?php declare(strict_types=1);

namespace Od\Scheduler\Async;

use Od\Scheduler\Async\JobMessageInterface;
use Od\Scheduler\Model\Job\JobRunner;
use Psr\Log\LoggerInterface;

class JobExecutionHandler
{
    private LoggerInterface $logger;
    private JobRunner $jobRunner;

    public function __construct(
        LoggerInterface $logger,
        JobRunner $jobRunner
    ) {
        $this->logger = $logger;
        $this->jobRunner = $jobRunner;
    }

    #[AsMessageHandler]
    public function __invoke(JobMessageInterface $message)
    {
        $this->handle($message);
    }

    /**
     * @param JobMessageInterface $message
     */
    public function handle(\Od\Scheduler\Async\JobMessageInterface $message): void
    {
        try {
            $this->jobRunner->execute($message);
        } catch (\Throwable $e) {
            // Should not trigger any exceptions to avoid message requeue
            $this->logger->error(
                \sprintf('Failed to run job[id: %s] | ' . get_class($message) . ' |  message: %s', $message->getJobId(), $e->getMessage()),
            );
        }
    }

    final public static function getHandledMessages(): iterable
    {
        return [JobMessageInterface::class];
    }
}
