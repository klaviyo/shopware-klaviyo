<?php declare(strict_types=1);

namespace Klaviyo\Integration\Entity\FlagStorage;

use Shopware\Core\Framework\DataAbstractionLayer\{EntityDefinition, FieldCollection};
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\{PrimaryKey, Required};
use Shopware\Core\Framework\DataAbstractionLayer\Field\{FkField, IdField, StringField};
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;

class FlagStorageDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'klaviyo_flag_storage';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return FlagStorageEntity::class;
    }

    public function getCollectionClass(): string
    {
        return FlagStorageCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        $idField = (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey());
        $key = (new StringField('key', 'key'))->addFlags(new Required());
        $value = (new StringField('value', 'value'))->addFlags(new Required());
        $salesChannelId = (new FkField('sales_channel_id', 'salesChannelId',
            SalesChannelDefinition::class))->addFlags(new Required());

        return new FieldCollection([
            $idField,
            $key,
            $value,
            $salesChannelId
        ]);
    }
}
