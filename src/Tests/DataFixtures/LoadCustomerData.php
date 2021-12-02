<?php

namespace Klaviyo\Integration\Tests\DataFixtures;

use Psr\Container\ContainerInterface;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class LoadCustomerData extends AbstractTestDataFixture
{
    private $customerData = [
        'klaviyo_tracking_integration.customer.foo' => [
            'salesChannel' => 'klaviyo_tracking_integration.sales_channel.storefront',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'customerNumber' => 'foo',
            'email' => 'foo@example.com',
            'active' => true,
        ],
    ];

    public function execute(ContainerInterface $container, ReferencesRegistry $referencesRegistry)
    {
        foreach ($this->customerData as $reference => $customerRecordData) {
            /** @var SalesChannelEntity $salesChannel */
            $salesChannel = $referencesRegistry->getByReference($customerRecordData['salesChannel']);
            unset($customerRecordData['salesChannel']);

            $customerRecordData['salesChannelId'] = $salesChannel->getId();
            $customerRecordData['languageId'] = $salesChannel->getLanguageId();

            $this->resolveReferencesAsIdsIfExists($referencesRegistry, $customerRecordData, ['']);

            $customerRepository = $container->get('customer.repository');

            /** @var CustomerEntity $customerEntity */
            $customerEntity = $this->createEntity($customerRepository, $customerRecordData);
            $referencesRegistry->setReference($reference, $customerEntity);
        }
    }

    public function getDependenciesList(): array
    {
        return [new RegisterDefaultSalesChannel()];
    }
}