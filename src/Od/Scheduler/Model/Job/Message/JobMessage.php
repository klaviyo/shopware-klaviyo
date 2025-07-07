<?php declare(strict_types=1);

namespace Klaviyo\Integration\Od\Scheduler\Model\Job\Message;

use Klaviyo\Integration\Od\Scheduler\Model\Job\JobRuntimeMessageInterface;
use Klaviyo\Integration\Od\Scheduler\Model\MessageManager;

Class JobMessage implements JobRuntimeMessageInterface
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