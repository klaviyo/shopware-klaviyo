<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Gateway\Domain\Profile\Search;

class ProfileIdSearchResult
{
    private array $profileIdCustomerIdMapping = [];
    private array $errors = [];

    public function addError(\Throwable $e): void
    {
        $this->errors[] = $e;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function addMapping(string $profileId, string $customerId): void
    {
        $this->profileIdCustomerIdMapping[$profileId] = $customerId;
    }

    public function getMapping(): array
    {
        return $this->profileIdCustomerIdMapping;
    }
}
