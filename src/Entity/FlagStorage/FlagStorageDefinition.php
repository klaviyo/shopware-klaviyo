<?php declare(strict_types=1);

namespace Klaviyo\Integration\Entity\FlagStorage;

use Shopware\Core\Framework\DataAbstractionLayer\{EntityDefinition, FieldCollection};
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\{PrimaryKey, Required};
use Shopware\Core\Framework\DataAbstractionLayer\Field\{IdField, StringField};

class FlagStorageDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'klaviyo_job_flag_storage';

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
        $key = (new StringField('type', 'type'))->addFlags(new Required());
        $value = (new StringField('type', 'type'))->addFlags(new Required());
        $hash = (new StringField('type', 'type'))->addFlags(new Required());

        return new FieldCollection([
            $idField,
            $key,
            $value,
            $hash
        ]);
    }
}
