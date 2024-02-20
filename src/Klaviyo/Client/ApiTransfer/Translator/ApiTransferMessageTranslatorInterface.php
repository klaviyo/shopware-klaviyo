<?php

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Translator;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Shopware\Core\Framework\Context;

interface ApiTransferMessageTranslatorInterface
{
    /**
     * @param object $request
     * @param Context|null $context
     * @return Request
     * @throws \Throwable
     */
    public function translateRequest(object $request, Context $context = null): Request;

    /**
     * @param ResponseInterface $response
     *
     * @return object
     * @throws \Throwable
     */
    public function translateResponse(ResponseInterface $response): object;

    public function isSupport(object $request): bool;
}
