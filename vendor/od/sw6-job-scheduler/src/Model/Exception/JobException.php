<?php declare(strict_types=1);

namespace Od\Scheduler\Model\Exception;

use Throwable;

class JobException extends \Exception
{
    private string $jobId;

    public function __construct(string $jobId, $message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->jobId = $jobId;
    }

    public function getJobId(): string
    {
        return $this->jobId;
    }
}
