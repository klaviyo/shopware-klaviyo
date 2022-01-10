<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client;

interface ClientInterface
{
    public function sendRequests(array $requests): ClientResult;
}
