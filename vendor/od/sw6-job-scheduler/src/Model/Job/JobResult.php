<?php declare(strict_types=1);

namespace Od\Scheduler\Model\Job;

class JobResult
{
    private array $errors;

    public function __construct(array $errors = [])
    {
        $this->errors = $errors;
    }

    public function addError(\Throwable $e): void
    {
        $this->errors[] = $e;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }
}
