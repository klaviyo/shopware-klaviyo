<?php

namespace Klaviyo\Integration\Model\Response;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

class KlaviyoBinaryFileResponse extends BinaryFileResponse
{
    public function setContent(?string $content)
    {
        // Added to fix problem with CSRF logic for responses
        return;
    }
}