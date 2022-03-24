<?php declare(strict_types=1);

namespace Klaviyo\Integration\Entity\NewsletterSubscriber;

use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;


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