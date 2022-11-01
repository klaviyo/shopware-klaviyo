<?php declare(strict_types=1);

namespace Klaviyo\Integration\System\Scheduling\ExcludedSubscriberSync;

class Result
{
    /**
     * @var array<string, string[]>
     */
    private array $emails = [];

    /**
     * @var \Throwable[]
     */
    private array $errors = [];

    public function addEmails(string $channelId, array $emails)
    {
        $this->emails[$channelId] = \array_merge($this->emails[$channelId] ?? [], $emails);
    }

    /**
     * @return array<string, string[]>
     */
    public function all(): array
    {
        return $this->emails;
    }

    public function addError(\Throwable $e): void
    {
        $this->errors[] = $e;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
