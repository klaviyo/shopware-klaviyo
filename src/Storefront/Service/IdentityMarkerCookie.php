<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Storefront\Service;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * JS-readable cookie telling the storefront whether the visitor has an identity
 * (customer or subscriber). Value is an HMAC of the id, so it changes with the identity.
 * The client-controlled klaviyo_subscriber cookie only counts when the marker matches.
 */
class IdentityMarkerCookie
{
    public const NAME = 'klaviyo_identified';

    private const VALUE_LENGTH = 16;

    public function __construct(
        private readonly string $secret,
    ) {
    }

    public function valueForCustomer(string $customerId): string
    {
        return $this->hash('customer:' . $customerId);
    }

    public function valueForSubscriber(string $subscriberId): string
    {
        return $this->hash('subscriber:' . $subscriberId);
    }

    public function isValidForSubscriber(Request $request, string $subscriberId): bool
    {
        $current = $this->read($request);

        return null !== $current && hash_equals($this->valueForSubscriber($subscriberId), $current);
    }

    public function read(Request $request): ?string
    {
        $value = $request->cookies->get(self::NAME);

        return \is_string($value) && $value !== '' ? $value : null;
    }

    public function set(Request $request, Response $response, string $value): void
    {
        $cookie = Cookie::create(self::NAME, $value, 0, '/', null, null, false);
        $cookie->setSecureDefault($request->isSecure());

        $response->headers->setCookie($cookie);
    }

    public function clear(Request $request, Response $response): void
    {
        $response->headers->clearCookie(self::NAME, '/', null, $request->isSecure(), false);
    }

    /**
     * The marker cookie queued on the response (set or cleared), if any.
     */
    public function findOn(Response $response): ?Cookie
    {
        foreach ($response->headers->getCookies() as $cookie) {
            if (self::NAME === $cookie->getName()) {
                return $cookie;
            }
        }

        return null;
    }

    /**
     * True when a fresh marker is already set on the response.
     */
    public function isSetOn(Response $response): bool
    {
        $cookie = $this->findOn($response);

        return null !== $cookie && !$cookie->isCleared();
    }

    private function hash(string $input): string
    {
        return substr(hash_hmac('sha256', $input, $this->secret), 0, self::VALUE_LENGTH);
    }
}
