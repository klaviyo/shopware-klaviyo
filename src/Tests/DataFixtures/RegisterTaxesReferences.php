<?php

namespace Klaviyo\Integration\Tests\DataFixtures;

use Psr\Container\ContainerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Tax\TaxEntity;

class RegisterTaxesReferences implements TestDataFixturesInterface
{
    public function execute(ContainerInterface $container, ReferencesRegistry $referencesRegistry)
    {
        /** @var EntityRepositoryInterface $taxRepository */
        $taxRepository = $container->get('tax.repository');
        $taxes = $taxRepository->search(new Criteria(), Context::createDefaultContext())->getEntities();

        /** @var TaxEntity $tax */
        foreach ($taxes as $tax) {
            switch ($tax->getName()) {
                case 'Standard rate':
                    $referencesRegistry->setReference('klaviyo_tracking_integration.tax.standard', $tax);
                    break;
                case 'Reduced rate':
                    $referencesRegistry->setReference('klaviyo_tracking_integration.tax.reduced', $tax);
                    break;
                case 'Reduced rate 2':
                    $referencesRegistry->setReference('klaviyo_tracking_integration.tax.reduced2', $tax);
                    break;
            }
        }
    }
}
