<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Entity\CheckoutMapping;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class CheckoutMappingDefinition extends EntityDefinition
{
    public const CART_TABLE = 'cart';
    public const ENTITY_NAME = 'klaviyo_checkout_mapping';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return CheckoutMappingEntity::class;
    }

    public function getCollectionClass(): string
    {
        return CheckoutMappingCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new StringField('reference', 'reference'))->addFlags(new Required()),
            (new StringField('mapping_table', 'mappingTable'))->addFlags(new Required()),
        ]);
    }
}
