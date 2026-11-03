<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Decorator\Storefront\Framework\Cookie;

use Klaviyo\Integration\Storefront\Service\IdentityMarkerCookie;
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
                'snippet_name' => 'cookie.klaviyo',
                'snippet_description' => 'klaviyo.cookie.description',
                'entries' => [
                    [
                        'snippet_name' => 'klaviyo.cookie.consent.value',
                        'snippet_description' => 'klaviyo.cookie.consent.description',
                        'cookie' => 'od-klaviyo-track-allow',
                        'value' => true,
                        'expiration' => '30',
                    ],
                    [
                        // set by IdentityMarkerCookieEventListener once consent is given, removed by the consent JS on decline
                        'snippet_name' => 'klaviyo.cookie.identified.value',
                        'snippet_description' => 'klaviyo.cookie.identified.description',
                        'cookie' => IdentityMarkerCookie::NAME,
                    ],
                ],
            ],
        ]);
    }
}
