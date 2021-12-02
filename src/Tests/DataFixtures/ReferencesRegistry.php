<?php

namespace Klaviyo\Integration\Tests\DataFixtures;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;

class ReferencesRegistry
{
    /**
     * @var array|string[]
     */
    private array $references;

    public function setReference(string $reference, $value, bool $allowReplaceExistingReference = false)
    {
        if (!$allowReplaceExistingReference && isset($this->references[$reference])) {
            throw new \LogicException(sprintf('Reference %s already registered', $reference));
        }

        $this->references[$reference] = $value;
    }

    public function getByReference(string $reference): Entity
    {
        if (!isset($this->references[$reference])) {
            throw new \LogicException(sprintf('Reference %s was not registered', $reference));
        }

        return $this->references[$reference];
    }
}
