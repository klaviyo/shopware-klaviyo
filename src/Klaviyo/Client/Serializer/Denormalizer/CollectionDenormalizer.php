<?php

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Denormalizer;

use Klaviyo\Integration\Utils\Collection\TypedCollection;
use Klaviyo\Integration\Utils\Reflection\ReflectionHelper;

class CollectionDenormalizer extends AbstractDenormalizer
{
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        /** @var TypedCollection $collection */
        $collection = new $type();

        foreach ($data as $row) {
            $denormalizedItem = $this->denormalizeValue($row, $collection->getItemClassName());
            $collection->add($denormalizedItem);
        }

        return $collection;
    }

    public function supportsDenormalization($data, string $type, string $format = null): bool
    {
        return ReflectionHelper::isClassInstanceOf($type, TypedCollection::class);
    }
}
