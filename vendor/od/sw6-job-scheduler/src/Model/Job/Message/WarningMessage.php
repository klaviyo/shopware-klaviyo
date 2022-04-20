<?php declare(strict_types=1);

namespace Od\Scheduler\Model\Job\Message;

use Od\Scheduler\Model\MessageManager;

class WarningMessage extends JobMessage
{
    public function getType(): string
    {
        return MessageManager::TYPE_WARNING;
    }
}