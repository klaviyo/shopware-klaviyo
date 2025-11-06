<?php declare(strict_types=1);

namespace Klaviyo\Integration\Storefront\Service\Cookie;

use Symfony\Component\HttpFoundation\RequestStack;

class CookieConsentService {

    private RequestStack $requestStack;

    public function __construct(
        RequestStack $requestStack
    ) {
        $this->requestStack = $requestStack;
    }

    /**
     * @param 'shopware' | 'consentmanager' | 'usercentrics' | 'cookiebot' $consentType
     * @return bool
     */
    public function hasConsent(string $consentType): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        switch ($consentType) {
            case 'shopware':
            case 'consentmanager':
            case 'usercentrics':
                $isAllowed = !!$request->cookies->get('od-klaviyo-track-allow');
                break;
            case 'cookiebot':
                $isAllowed = $this->isCookieBotAllowed();
                break;
            default:
                $isAllowed = true;
                break;
        }

        return $isAllowed;
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
