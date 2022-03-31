<?php declare(strict_types=1);

namespace Klaviyo\Integration\Entity\FlagStorage;

use Shopware\Core\Framework\DataAbstractionLayer\{Entity, EntityIdTrait};

class FlagStorageEntity extends Entity
{
    use EntityIdTrait;

    protected string $key;
    protected string $value;
    protected string $hash;

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function setHash(string $hash): void
    {
        $this->hash = $hash;
    }
}
