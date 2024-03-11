<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Gateway\Domain\Profile\Search\Strategy;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Search\GetProfileIdResponse;
use Klaviyo\Integration\Klaviyo\Gateway\ClientRegistry;
use Klaviyo\Integration\Klaviyo\Gateway\Domain\Profile\Search\ProfileIdSearchResult;
use Klaviyo\Integration\Klaviyo\Gateway\Translator\GetProfileIdByFieldRequestTranslatorInterface;
use Shopware\Core\Checkout\Customer\CustomerCollection;
use Shopware\Core\Framework\Context;

class SearchByFieldStrategy implements SearchStrategyInterface
{
    private ClientRegistry $clientRegistry;
    private GetProfileIdByFieldRequestTranslatorInterface $byCustomerFieldRequestTranslator;

    public function __construct(
        ClientRegistry $clientRegistry,
        GetProfileIdByFieldRequestTranslatorInterface $byCustomerFieldRequestTranslator
    ) {
        $this->clientRegistry = $clientRegistry;
        $this->byCustomerFieldRequestTranslator = $byCustomerFieldRequestTranslator;
    }

    /**
     * @throws \Exception
     */
    public function searchProfilesIds(
        Context $context,
        string $channelId,
        CustomerCollection $customers
    ): ProfileIdSearchResult {
        $searchResult = new ProfileIdSearchResult();
        $searchRequests = [];
        $client = $this->clientRegistry->getClient($channelId);

        foreach ($customers as $customer) {
            $searchRequests[] = $this->byCustomerFieldRequestTranslator->translateToGetProfileIdRequest($customer);
        }

        $clientResult = $client->sendRequests($searchRequests);

        foreach ($searchRequests as $request) {
            /** @var GetProfileIdResponse $response */
            $response = $clientResult->getRequestResponse($request);

            if (!$response->isSuccess()) {
                $searchResult->addError(
                    new \Exception($response->getErrorDetails() ?? 'Profile Search API response fault.')
                );
                continue;
            }

            if ($response->getProfileId()) {
                $searchResult->addMapping($response->getProfileId(), $request->getCustomer()->getId());
            }
        }

        return $searchResult;
    }
}
