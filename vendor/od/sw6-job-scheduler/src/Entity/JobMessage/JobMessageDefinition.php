<?php declare(strict_types=1);

namespace Od\Scheduler\Entity\JobMessage;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class JobMessageDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'od_scheduler_job_message';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return JobMessageEntity::class;
    }

    public function getCollectionClass(): string
    {
        return JobMessageCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        $idField = new IdField('id', 'id');
        $idField->addFlags(new Flag\Required(), new Flag\PrimaryKey(), new Flag\ApiAware());

        $jobIdField = new IdField('job_id', 'jobId');
        $jobIdField->addFlags(new Flag\Required(), new Flag\ApiAware());

        $typeField = new StringField('type', 'type');
        $typeField->addFlags(new Flag\Required(), new Flag\ApiAware());

        $messageField = new LongTextField('message', 'message');
        $messageField->addFlags(new Flag\Required(), new Flag\ApiAware());

        return new FieldCollection([
            $idField,
            $jobIdField,
            $typeField,
            $messageField
        ]);
    }
}
