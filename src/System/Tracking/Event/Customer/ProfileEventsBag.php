<?php declare(strict_types=1);

namespace Klaviyo\Integration\System\Tracking\Event\Customer;

use Shopware\Core\Checkout\Customer\CustomerCollection;
use Shopware\Core\Checkout\Customer\CustomerEntity;

class ProfileEventsBag
{
    private array $channelCustomerMap = [];

    public function add(CustomerEntity $customer): void
    {
        $this->channelCustomerMap[$customer->getSalesChannelId()][$customer->getId()] = $customer;
    }

    /**
     * @return array<string, CustomerEntity[]>
     */
    public function all(): array
    {
        return $this->channelCustomerMap;
    }

    public static function fromCollection(CustomerCollection $collection): ProfileEventsBag
    {
        $bag = new self();
        foreach ($collection as $customer) {
            $bag->add($customer);
        }

        return $bag;
    }
}
