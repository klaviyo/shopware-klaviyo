<?php

namespace Klaviyo\Integration\Utils\Collection;

use Shopware\Core\Framework\Struct\Collection;

abstract class TypedCollection extends Collection
{
    protected function getExpectedClass(): ?string
    {
        return $this->getItemClassName();
    }

    abstract public function getItemClassName(): string;

    public function split(int $size): array
    {
        $chunks = array_chunk($this->elements, $size);
        foreach ($chunks as &$chunk) {
            $className = get_called_class();
            $chunk = new $className($chunk);
        }

        return $chunks;
    }
}