<?php

namespace Klaviyo\Integration\Entity\Job;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class JobDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'klaviyo_job';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return JobEntity::class;
    }

    public function getCollectionClass(): string
    {
        return JobCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        $idField = new IdField('id', 'id');
        $idField->addFlags(new Required(), new PrimaryKey(), new ApiAware());
        $parentIdField = new IdField('parent_id', 'parentId');

        $statusField = (new StringField('status', 'status'))->addFlags(new Required(), new ApiAware());
        $typeField = (new StringField('type', 'type'))->addFlags(new Required(), new ApiAware());
        $nameField = (new StringField('name', 'name'))->addFlags(new Required(), new ApiAware());
        $messageField = new LongTextField('message', 'message');
        $messageField->addFlags(new ApiAware());

        $startedAtField = new DateTimeField('started_at', 'startedAt');
        $startedAtField->addFlags(new ApiAware());
        $finishedAtField = new DateTimeField('finished_at', 'finishedAt');
        $finishedAtField->addFlags(new ApiAware());

        return new FieldCollection([
            $idField,
            $parentIdField,
            $statusField,
            $typeField,
            $nameField,
            $messageField,
            $startedAtField,
            $finishedAtField
        ]);
    }
}
