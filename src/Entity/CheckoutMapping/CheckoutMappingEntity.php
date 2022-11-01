<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Entity\CheckoutMapping;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class CheckoutMappingEntity extends Entity
{
    use EntityIdTrait;

    protected string $reference;
    protected string $mappingTable;

    public function getReference(): string
    {
        return $this->reference;
    }

    public function setReference(string $reference): void
    {
        $this->reference = $reference;
    }

    public function getMappingTable(): string
    {
        return $this->mappingTable;
    }

    public function setMappingTable(string $mappingTable): void
    {
        $this->mappingTable = $mappingTable;
    }
}
