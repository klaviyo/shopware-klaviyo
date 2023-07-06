<?php

namespace Klaviyo\Integration\Entity\Helper;

use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use Shopware\Core\System\Country\CountryEntity;

class AddressDataHelper
{
    private EntityRepository $stateRepository;
    private EntityRepository $countryRepository;

    public function __construct(
        EntityRepository $stateRepository,
        EntityRepository $countryRepository
    ) {
        $this->stateRepository = $stateRepository;
        $this->countryRepository = $countryRepository;
    }

    /**
     * @param OrderAddressEntity|CustomerAddressEntity $addressEntity
     *
     * @return CountryEntity|null
     */
    public function getAddressCountry(Context $context, $addressEntity): ?CountryEntity
    {
        if (!$addressEntity || !$addressEntity->getCountryId()) {
            return null;
        }

        if ($addressEntity->getCountry()) {
            $country = $addressEntity->getCountry();
        } else {
            $countryId = $addressEntity->getCountryId();
            $country = $this->countryRepository
                ->search(new Criteria([$countryId]), $context)
                ->first();
        }

        return $country;
    }

    /**
     * @param Context $context
     * @param OrderAddressEntity|CustomerAddressEntity $addressEntity
     *
     * @return CountryStateEntity|null
     */
    public function getAddressRegion(Context $context, $addressEntity): ?CountryStateEntity
    {
        if (!$addressEntity || !$addressEntity->getCountryStateId()) {
            return null;
        }

        if ($addressEntity->getCountryState()) {
            $state = $addressEntity->getCountryState();
        } else {
            $stateId = $addressEntity->getCountryStateId();
            $state = $this->stateRepository
                ->search(new Criteria([$stateId]), $context)
                ->first();
        }

        return $state;
    }
}