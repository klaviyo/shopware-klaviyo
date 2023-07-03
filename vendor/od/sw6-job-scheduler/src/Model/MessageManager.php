<?php declare(strict_types=1);

namespace Od\Scheduler\Model;

use Od\Scheduler\Model\Exception\JobException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

class MessageManager
{
    public const TYPE_INFO = 'info-message';
    public const TYPE_ERROR = 'error-message';
    public const TYPE_WARNING = 'warning-message';

    private EntityRepository $jobMessageRepository;

    public function __construct(EntityRepository $jobMessageRepository)
    {
        $this->jobMessageRepository = $jobMessageRepository;
    }

    public function addInfoMessage(string $jobId, string $message)
    {
        $this->addMessage($jobId, $message,self::TYPE_INFO);
    }

    public function addWarningMessage(string $jobId, string $message)
    {
        $this->addMessage($jobId, $message,self::TYPE_WARNING);
    }

    public function addErrorMessage(string $jobId, string $message)
    {
        $this->addMessage($jobId, $message,self::TYPE_ERROR);
    }

    public function addExceptionMessage(JobException $jobException)
    {
        $this->addErrorMessage($jobException->getJobId(), $jobException->getMessage());
    }

    public function addMessage(string $jobId, string $message, string $type)
    {
        $this->jobMessageRepository->create([
            [
                'jobId' => $jobId,
                'type' => $type,
                'message' => $message
            ]
        ], Context::createDefaultContext());
    }
}
