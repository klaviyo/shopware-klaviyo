<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Decorator\Storefront\Framework\Cookie;

use Shopware\Storefront\Framework\Cookie\CookieProviderInterface;

class KlaviyoCookieProvider implements CookieProviderInterface
{
    private CookieProviderInterface $originalService;

    public function __construct(CookieProviderInterface $service)
    {
        $this->originalService = $service;
    }

    public function getCookieGroups(): array
    {
        return array_merge($this->originalService->getCookieGroups(), [
            [
                'snippet_name' => 'klaviyo.cookie.value',
                'snippet_description' => 'klaviyo.cookie.description',
                'cookie' => 'od-klaviyo-track-allow',
                'value' => true,
                'expiration' => '30',
            ],
        ]);
    }
}
