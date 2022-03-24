<?php declare(strict_types=1);

namespace Klaviyo\Integration\Entity\NewsletterSubscriber;

use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Framework\Routing\Annotation\Entity;

class NewsletterSubscriberEntity extends Entity
{
    use EntityIdTrait;

    protected string $email;

    protected \DateTimeInterface $createdAt;

    protected \DateTimeInterface $updatedAt;

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail($email): void
    {
        $this->email = $email;
    }
}