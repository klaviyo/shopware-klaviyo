<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Denormalizer;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Account\GetAccountResponse;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Translator\GetAccountApiTransferTranslator;

/**
 * @method getSupportedTypes(?string $format)
 */
class GetAccountDenormalizer extends AbstractDenormalizer
{
    public function denormalize(mixed $data, string $type, string $format = null, array $context = [])
    {
        $error = '';
        $successStatus = true;
        $responsePublicKey = '';
        $code = '';

        if (isset($data['errors']) && isset(current($data['errors'])['code'])) {
            $code = current($data['errors'])['code'];

            if (GetAccountApiTransferTranslator::PERMISSION_DENIED !== current($data['errors'])['code']) {
                $error = current($data['errors'])['detail'];
                $successStatus = false;
            }
        } else {
            if (isset($data['data']['attributes']['public_api_key'])) {
                $responsePublicKey = $data['data']['attributes']['public_api_key'];
            }
        }

        return new GetAccountResponse($successStatus, $responsePublicKey, $code, $error);
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null): bool
    {
        return GetAccountResponse::class === $type;
    }
}
