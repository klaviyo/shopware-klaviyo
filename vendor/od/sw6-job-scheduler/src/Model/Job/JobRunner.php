<?php declare(strict_types=1);

namespace Od\Scheduler\Model\Job;

use Od\Scheduler\Async\{JobMessageInterface, ParentAwareMessageInterface};
use Od\Scheduler\Entity\Job\JobEntity;
use Od\Scheduler\Model\Exception\JobException;
use Od\Scheduler\Model\MessageManager;

class JobRunner
{
    const NOT_FINISHED_STATUSES = [
        JobEntity::TYPE_PENDING,
        JobEntity::TYPE_RUNNING
    ];

    private MessageManager $messageManager;
    private HandlerPool $handlerPool;
    private JobHelper $jobHelper;

    public function __construct(
        MessageManager $messageManager,
        HandlerPool $handlerPool,
        JobHelper $jobHelper
    ) {
        $this->messageManager = $messageManager;
        $this->handlerPool = $handlerPool;
        $this->jobHelper = $jobHelper;
    }

    public function execute(JobMessageInterface $message): JobResult
    {
        $result = null;
        $handler = $this->handlerPool->get($message->getHandlerCode());

        try {
            $this->jobHelper->markJob($message->getJobId(), JobEntity::TYPE_RUNNING);
            $result = $handler->execute($message);
        } catch (\Throwable $e) {
            $result = $result !== null ? $result : new JobResult();
            $result->addError(new JobException($message->getJobId(), $e->getMessage()));
        }

        return $this->postProcessResult($result, $handler, $message);
    }

    /**
     * This method is used as built-in job execution behavior like "SkipErrors".
     * TODO: Its really nice to have option to take control over the job execution behavior like StopOnError, SkipErrors
     * TODO: I believe in can be easily implemented by wrapping handler into the specific behavior, witch will take
     * TODO: control over the whole job-chain processing.
     *
     * Current behavior example with all finished child jobs:
     * [parent generation job (error)]
     *      |-> [child 1 (status: succeed)]
     *      |-> [child 2 (status: succeed)]
     *      |-> [child 3 (status: error)]
     *
     * Current behavior example with not all finished child jobs:
     * [parent generation job (running)]
     *      |-> [child 1 (status: succeed)]
     *      |-> [child 2 (status: running)]
     *      |-> [child 3 (status: pending)]
     */
    private function postProcessResult(
        JobResult $result,
        JobHandlerInterface $handler,
        JobMessageInterface $message
    ): JobResult {
        foreach ($result->getMessages() as $resultMessage) {
            $this->messageManager->addMessage(
                $message->getJobId(),
                $resultMessage->getMessage(),
                $resultMessage->getType()
            );
        }
        $status = $result->hasErrors() ? JobEntity::TYPE_FAILED : JobEntity::TYPE_SUCCEED;

        if ($handler instanceof GeneratingHandlerInterface) {
            if ($status === JobEntity::TYPE_FAILED) {
                $this->jobHelper->markJob($message->getJobId(), $status);
            } else if ($this->jobHelper->getChildJobs($message->getJobId())->count() === 0) {
                /**
                 * Nothing was scheduled by generating job handler - delete job.
                 */
                $this->jobHelper->deleteJob($message->getJobId());
            }

            return $result;
        }

        $this->jobHelper->markJob($message->getJobId(), $status);

        if ($message instanceof ParentAwareMessageInterface) {
            $parentJobId = $message->getParentJobId();
            if ($this->jobHelper->getChildJobs($parentJobId, self::NOT_FINISHED_STATUSES)->count() === 0) {
                $hasFailedChild = $this->jobHelper->getChildJobs($parentJobId, [JobEntity::TYPE_FAILED])->count() !== 0;
                /**
                 * All current job's siblings was executed - mark parent job with proper status
                 * according to existence of failed child jobs.
                 */
                $this->jobHelper->markJob(
                    $parentJobId,
                    $hasFailedChild ? JobEntity::TYPE_FAILED : JobEntity::TYPE_SUCCEED
                );

                if ($hasFailedChild) {
                    $this->messageManager->addErrorMessage($parentJobId, 'Some child jobs have failed to process.');
                }
            }
        }

        return $result;
    }
}
