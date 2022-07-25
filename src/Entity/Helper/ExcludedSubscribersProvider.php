<?php declare(strict_types=1);

namespace Klaviyo\Integration\Entity\Helper;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\ExcludedSubscribers\GetExcludedSubscribers\Response;
use Klaviyo\Integration\Klaviyo\Gateway\KlaviyoGateway;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

// TODO: move this class outside mysterious "Helper" namespace
class ExcludedSubscribersProvider
{
    public const DEFAULT_COUNT_PER_PAGE = 500;
    private KlaviyoGateway $klaviyoGateway;

    public function __construct(KlaviyoGateway $klaviyoGateway)
    {
        $this->klaviyoGateway = $klaviyoGateway;
    }

    /**
     * @param string $channelId
     * @param int $page
     *
     * @return \Generator|Response[]
     * @throws \Exception
     */
    public function getExcludedSubscribers(string $channelId, int $page): \Generator
    {
        $currentPage = $page;
        $result = $this->klaviyoGateway->getExcludedSubscribersFromList(
            $channelId,
            self::DEFAULT_COUNT_PER_PAGE,
            $page
        );
        $totalEmailsValue = $result->getTotalEmailsCount();
        $quantityOfPages = $totalEmailsValue == self::DEFAULT_COUNT_PER_PAGE
            ? 0
            : floor($totalEmailsValue / self::DEFAULT_COUNT_PER_PAGE);
        yield $result;

        while ($quantityOfPages > $currentPage) {
            $currentPage++;
            yield $this->klaviyoGateway->getExcludedSubscribersFromList(
                $channelId,
                self::DEFAULT_COUNT_PER_PAGE,
                $currentPage
            );
        }
    }
}
