<?php declare(strict_types=1);

namespace Klaviyo\Integration\Entity\Event;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class EventDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'klaviyo_job_event';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return EventEntity::class;
    }

    public function getCollectionClass(): string
    {
        return EventCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        $idField = (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey());
        $entityIdField = (new IdField('entity_id', 'entityId'))->addFlags(new Required());
        $channelIdField = (new IdField('sales_channel_id', 'salesChannelId'))->addFlags(new Required());

        $typeField = (new StringField('type', 'type'))->addFlags(new Required());
        $happenedAtField = (new DateTimeField('happened_at', 'happenedAt'))->addFlags(new Required());

        return new FieldCollection([
            $idField,
            $typeField,
            $entityIdField,
            $channelIdField,
            $happenedAtField,
        ]);
    }
}
