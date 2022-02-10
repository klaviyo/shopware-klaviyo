<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Gateway;

use Shopware\Core\System\SalesChannel\SalesChannelEntity;

interface GetListIdByListNameInterface
{
    public function execute(SalesChannelEntity $salesChannelEntity, string $listName): string;
}
