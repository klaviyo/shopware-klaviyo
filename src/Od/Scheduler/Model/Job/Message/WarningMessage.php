<?php declare(strict_types=1);

namespace Klaviyo\Integration\Od\Scheduler\Model\Job\Message;

use Klaviyo\Integration\Od\Scheduler\Model\MessageManager;

class WarningMessage extends JobMessage
{
    public function getType(): string
    {
        return MessageManager::TYPE_WARNING;
    }
}