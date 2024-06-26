<?php

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Denormalizer;

use Klaviyo\Integration\Utils\Collection\TypedCollection;
use Klaviyo\Integration\Utils\Reflection\ReflectionHelper;
use Symfony\Component\Serializer\Normalizer\DenormalizableInterface;
use Symfony\Component\Serializer\Normalizer\NormalizableInterface;

class CollectionDenormalizer extends AbstractDenormalizer
{
    /**
     * @param array $data
     * @param string $type
     * @param string|null $format
     * @param array $context
     *
     * @return TypedCollection
     */
    public function denormalize($data, string $type, string $format = null, array $context = []): TypedCollection
    {
        /** @var TypedCollection $collection */
        $collection = new $type();

        foreach ($data as $row) {
            $denormalizedItem = $this->denormalizeValue($row, $collection->getItemClassName());
            $collection->add($denormalizedItem);
        }

        return $collection;
    }

    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        return ReflectionHelper::isClassInstanceOf($type, TypedCollection::class);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            NormalizableInterface::class => true,
            DenormalizableInterface::class => true,
        ];
    }
}
