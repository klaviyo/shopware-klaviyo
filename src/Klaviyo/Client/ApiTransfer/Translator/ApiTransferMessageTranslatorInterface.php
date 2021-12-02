<?php

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Translator;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

interface ApiTransferMessageTranslatorInterface
{
    /**
     * @param object $request
     *
     * @return Request
     * @throws \Throwable
     */
    public function translateRequest(object $request): Request;

    /**
     * @param ResponseInterface $response
     *
     * @return object
     * @throws \Throwable
     */
    public function translateResponse(ResponseInterface $response): object;

    public function isSupport(object $request): bool;
}