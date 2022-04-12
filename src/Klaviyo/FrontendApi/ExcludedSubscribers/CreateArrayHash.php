<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\FrontendApi\ExcludedSubscribers;

class CreateArrayHash
{
    public static function execute(array $data): string
    {
        return md5(serialize($data));
    }
}
