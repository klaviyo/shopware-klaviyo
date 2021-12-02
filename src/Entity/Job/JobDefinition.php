<?php

namespace Klaviyo\Integration\Entity\Job;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class JobDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'klaviyo_job';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return JobCollection::class;
    }

    public function getEntityClass(): string
    {
        return JobEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        $idField = new IdField('id', 'id');
        $idField->addFlags(new Required(), new PrimaryKey());

        $typeField = new StringField('type', 'type');
        $typeField->addFlags(new Required());

        $statusField = new StringField('status', 'status');
        $statusField->addFlags(new Required());

        $activeField = new BoolField('active', 'active');
        $activeField->addFlags(new Required());

        $createdByScheduleField = new BoolField('created_by_schedule', 'createdBySchedule');
        $createdByScheduleField->addFlags(new Required());

        $startedAtField = new DateTimeField('started_at', 'startedAt');
        $finishedAtField = new DateTimeField(
            'finished_at',
            'finishedAt'
        );

        return new FieldCollection(
            [
                $idField,
                $typeField,
                $statusField,
                $activeField,
                $createdByScheduleField,
                $startedAtField,
                $finishedAtField
            ]
        );
    }
}