<?php declare(strict_types=1);

namespace Klaviyo\Integration\Entity\NewsletterSubscriber;

use Shopware\Core\Framework\DataAbstractionLayer\{EntityIdTrait, Entity};

class NewsletterSubscriberEntity extends Entity
{
    use EntityIdTrait;

    protected string $email;

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail($email): void
    {
        $this->email = $email;
    }
}