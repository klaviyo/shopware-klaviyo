<?php declare(strict_types=1);

namespace Od\Scheduler\Entity\Job;

use Od\Scheduler\Entity\JobMessage\JobMessageDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Inherited;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class JobDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'od_scheduler_job';

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
        $idField->addFlags(new Flag\Required(), new Flag\PrimaryKey(), new Flag\ApiAware());

        $parentIdField = new IdField('parent_id', 'parentId');

        $statusField = new StringField('status', 'status');
        $statusField->addFlags(new Flag\Required(), new Flag\ApiAware());

        $typeField = new StringField('type', 'type');
        $typeField->addFlags(new Flag\Required(), new Flag\ApiAware());

        $nameField = new StringField('name', 'name');
        $nameField->addFlags(new Flag\Required(), new Flag\ApiAware());

        $messageField = new LongTextField('message', 'message');
        $messageField->addFlags(new Flag\ApiAware());

        $startedAtField = new DateTimeField('started_at', 'startedAt');
        $startedAtField->addFlags(new Flag\ApiAware());

        $finishedAtField = new DateTimeField('finished_at', 'finishedAt');
        $finishedAtField->addFlags(new Flag\ApiAware());

        $messages = new OneToManyAssociationField('messages', JobMessageDefinition::class, 'job_id', 'id');
        $messages->addFlags(new Flag\ApiAware());

        $subJobs = new OneToManyAssociationField('subJobs', __CLASS__, 'parent_id', 'id');
        $subJobs->addFlags(new Flag\ApiAware());

        return new FieldCollection([
            $idField,
            $parentIdField,
            $statusField,
            $typeField,
            $nameField,
            $messageField,
            $startedAtField,
            $finishedAtField,
            $messages,
            $subJobs
        ]);
    }
}
