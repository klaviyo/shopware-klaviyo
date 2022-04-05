<?php declare(strict_types=1);

namespace Klaviyo\Integration\Entity\Helper;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\ExcludedSubscribers\GetExcludedSubscribers\GetExcludedSubscribersResponse;
use Klaviyo\Integration\Klaviyo\Gateway\KlaviyoGateway;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class ExcludedSubscribersHelper
{
    public const DEFAULT_COUNT_PER_PAGE = '500';
    private KlaviyoGateway $klaviyoGateway;

    public function __construct(KlaviyoGateway $klaviyoGateway)
    {
        $this->klaviyoGateway = $klaviyoGateway;
    }

    /**
     * @throws \Exception
     */
    public function generateExcludedSubscribers(
        SalesChannelEntity $channel,
        int $page
    ): \Generator {
        $currentPage = $page;
        $result = $this->getExcludedSubscribers($channel, self::DEFAULT_COUNT_PER_PAGE, $page);
        $totalEmailsValue = $result->getTotalEmailsValue();
        $quantityOfPages =
            $totalEmailsValue == self::DEFAULT_COUNT_PER_PAGE ?
            0 :
            floor($totalEmailsValue / self::DEFAULT_COUNT_PER_PAGE);
        yield $result;

        while ($quantityOfPages > $currentPage) {
            $currentPage++;
            yield $this->getExcludedSubscribers($channel, self::DEFAULT_COUNT_PER_PAGE, $currentPage);
        }

        return $result;
    }

    /**
     * @throws \Exception
     */
    public function getExcludedSubscribers(
        SalesChannelEntity $channel,
        string $count,
        $page
    ): GetExcludedSubscribersResponse {
        return $this->klaviyoGateway->getExcludedSubscribersFromList($channel, $count, $page);
    }
}