<?php declare(strict_types=1);

namespace Klaviyo\Integration\Entity\CartRequest;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class CartRequestDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'klaviyo_job_cart_request';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return CartRequestEntity::class;
    }

    public function getCollectionClass(): string
    {
        return CartRequestCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        $idField = (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey());
        $channelIdField = (new IdField('sales_channel_id', 'salesChannelId'))->addFlags(new Required());
        $requestField = new LongTextField('serialized_request', 'serializedRequest');

        return new FieldCollection([
            $idField,
            $channelIdField,
            $requestField,
        ]);
    }
}
