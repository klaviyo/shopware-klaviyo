<?php declare(strict_types=1);

namespace Klaviyo\Integration\Storefront\Service\Cookie;

use Symfony\Component\HttpFoundation\RequestStack;

class CookieConsentService {

    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * @param 'shopware' | 'consentmanager' | 'usercentrics' | 'cookiebot' $consentType
     * @return bool
     */
    public function hasConsent(string $consentType): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        return match ($consentType) {
            'shopware', 'consentmanager', 'usercentrics' => !!$request->cookies->get('od-klaviyo-track-allow'),
            'cookiebot' => $this->isCookieBotAllowed(),
            default => true,
        };
    }

    public function isCookieBotAllowed(): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        $data = $request->cookies->get('CookieConsent');

        if (!$data) {
            return false;
        }

        $valid_php_json = preg_replace(
            '/\s*:\s*([a-zA-Z0-9_]+?)([}\[,])/',
            ':"$1"$2',
            preg_replace(
                '/([{\[,])\s*([a-zA-Z0-9_]+?):/',
                '$1"$2":',
                str_replace("'", '"', stripslashes($data))
            )
        );

        $CookieConsent = json_decode($valid_php_json, true);

        return !empty($CookieConsent['marketing']) && 'true' === $CookieConsent['marketing'];
    }
}
