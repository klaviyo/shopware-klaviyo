<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Entity\Helper;

use Klaviyo\Integration\Klaviyo\Gateway\KlaviyoGateway;

// TODO: move this class outside mysterious "Helper" namespace
class ExcludedSubscribersProvider
{
    public const DEFAULT_COUNT_PER_PAGE = 100;
    private KlaviyoGateway $klaviyoGateway;

    public function __construct(KlaviyoGateway $klaviyoGateway)
    {
        $this->klaviyoGateway = $klaviyoGateway;
    }

    /**
     * @param string $channelId
     * @return \Generator
     *
     * @throws \Exception
     */
    public function getExcludedSubscribers(string $channelId): \Generator
    {
        $result = $this->klaviyoGateway->getExcludedSubscribersFromList(
            $channelId,
            self::DEFAULT_COUNT_PER_PAGE
        );

        yield $result;

        $nextPageUrl = $result->getNextPageUrl();

        while (null !== $nextPageUrl) {
            $result = $this->klaviyoGateway->getExcludedSubscribersFromList(
                $channelId,
                self::DEFAULT_COUNT_PER_PAGE,
                $nextPageUrl
            );

            $nextPageUrl = $result->getNextPageUrl();

            yield $result;
        }
    }
}
