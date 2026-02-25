<?php declare(strict_types=1);

namespace Klaviyo\Integration\Storefront\Service\Cookie;

use Symfony\Component\HttpFoundation\RequestStack;

class CookieConsentService {

    public const MANAGED_CONSENT_TYPES = [
        'shopware',
        'consentmanager',
        'usercentrics',
        'cookiebot'
    ];

    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }
    /**
     * @param 'shopware' | 'consentmanager' | 'usercentrics' | 'cookiebot' $consentType
     * @return bool
     */
    public function hasConsent(string $consentType): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return false;
        }

        if (in_array($consentType, self::MANAGED_CONSENT_TYPES, true)) {
            return (bool) $request->cookies->get('od-klaviyo-track-allow');
        }

        return true;
    }
}
