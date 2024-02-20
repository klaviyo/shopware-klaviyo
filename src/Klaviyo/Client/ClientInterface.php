<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client;

use Shopware\Core\Framework\Context;

interface ClientInterface
{
    public function sendRequests(array $requests, Context $context = null): ClientResult;
}
