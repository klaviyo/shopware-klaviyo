<?php declare(strict_types=1);

namespace Klaviyo\Integration\Entity\NewsletterSubscriber;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\{PrimaryKey, Required};
use Shopware\Core\Framework\DataAbstractionLayer\Field\{IdField, StringField};
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;


class NewsletterSubscriberDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'klaviyo_newsletter_subscriber';
    }

    public function getCollectionClass(): string
    {
        return NewsletterSubscriberCollection::class;
    }

    public function getEntityClass(): string
    {
        return NewsletterSubscriberEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection(
            [
                (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
                (new StringField('email', 'email'))->addFlags(new Required())
            ]
        );
    }
}