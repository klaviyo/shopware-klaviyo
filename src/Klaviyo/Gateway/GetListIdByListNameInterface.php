<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Gateway;

interface GetListIdByListNameInterface
{
    public function execute(string $salesChannelEntityId, string $listName): string;
}
