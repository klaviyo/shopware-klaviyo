<?php

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Denormalizer;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common\ProfileInfo;
use Klaviyo\Integration\Klaviyo\Client\Exception\DeserializationException;

class ProfileInfoDenormalizer extends AbstractDenormalizer
{
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $this->assertResultRow($data);

        return new ProfileInfo($data['id'], $data['email']);
    }

    private function assertResultRow($resultRow)
    {
        if (!is_array($resultRow)) {
            throw new DeserializationException('Decoded profile info value expected to be an array');
        }

        if (empty($resultRow['id'])) {
            throw new DeserializationException('Decoded profile info array expected to have an id key');
        }

        if (empty($resultRow['email'])) {
            throw new DeserializationException('Decoded profile info array expected to have an email key');
        }
    }

    public function supportsDenormalization($data, string $type, string $format = null)
    {
        return $type === ProfileInfo::class;
    }
}