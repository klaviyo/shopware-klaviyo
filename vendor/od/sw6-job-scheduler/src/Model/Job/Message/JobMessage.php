<?php declare(strict_types=1);

namespace Od\Scheduler\Model\Job\Message;

use Od\Scheduler\Model\Job\JobRuntimeMessageInterface;
use Od\Scheduler\Model\MessageManager;

class JobMessage implements JobRuntimeMessageInterface
{
    private string $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }

    public function getType(): string
    {
        return MessageManager::TYPE_INFO;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}