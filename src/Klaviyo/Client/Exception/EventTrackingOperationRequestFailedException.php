<?php

namespace Klaviyo\Integration\Klaviyo\Client\Exception;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class EventTrackingOperationRequestFailedException extends ClientException
{
    public function __construct(
        string $message,
        Throwable $previous = null,
        ?Request $request = null,
        ?ResponseInterface $response = null
    ) {
        parent::__construct($message, 0, $previous);

        if ($request) {
            $this->addToLoggableContext('request', $request);
        }
        if ($response) {
            $this->addToLoggableContext('response', $response);
        }
    }
}