<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\ExcludedSubscribers\Common;

use Shopware\Core\Framework\Struct\Collection;

class ExcludedSubscribersCollection extends Collection
{
    public function getItemClassName(): ?string
    {
        return ExcludedSubscribers::class;
    }
}