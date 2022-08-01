<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Gateway\Domain\Profile\Search\Strategy;

use Klaviyo\Integration\Klaviyo\Gateway\Domain\Profile\Search\ProfileIdSearchResult;
use Shopware\Core\Checkout\Customer\CustomerCollection;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;

class CompositeSearchStrategy implements SearchStrategyInterface
{
    /**
     * @var SearchStrategyInterface[]
     */
    private iterable $strategies;

    public function __construct(iterable $strategies)
    {
        $this->strategies = $strategies;
    }

    public function searchProfilesIds(Context $context, string $channelId, CustomerCollection $customers): ProfileIdSearchResult
    {
        $foundCustomerIds = [];
        $searchResult = new ProfileIdSearchResult();

        foreach ($this->strategies as $searchStrategy) {
            $customerSearchCollection = $customers->filter(function (CustomerEntity $customer) use ($foundCustomerIds) {
                return !in_array($customer->getId(), $foundCustomerIds);
            });

            $strategySearchResult = $searchStrategy->searchProfilesIds($context, $channelId, $customerSearchCollection);
            $foundCustomerIds = array_merge(array_values($strategySearchResult->getMapping()), $foundCustomerIds) ?? [];

            foreach ($strategySearchResult->getMapping() as $profileId => $customerId) {
                $searchResult->addMapping($profileId, $customerId);
            }

            foreach ($strategySearchResult->getErrors() as $error) {
                $searchResult->addError($error);
            }
        }

        return $searchResult;
    }
}
