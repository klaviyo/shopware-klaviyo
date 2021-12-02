<?php

namespace Klaviyo\Integration\Test\Mock;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class GuzzleClientTestProxy implements ClientInterface
{
    private ClientInterface $proxiedClient;

    public function setProxiedClient(ClientInterface $proxiedClient): void
    {
        $this->proxiedClient = $proxiedClient;
    }

    public function send(RequestInterface $request, array $options = []): ResponseInterface
    {
        return $this->proxiedClient->send($request, $options);
    }

    public function sendAsync(RequestInterface $request, array $options = []): PromiseInterface
    {
        return $this->proxiedClient->sendAsync($request, $options);
    }

    public function request(string $method, $uri, array $options = []): ResponseInterface
    {
        return $this->proxiedClient->request($method, $uri, $options);
    }

    public function requestAsync(string $method, $uri, array $options = []): PromiseInterface
    {
        return $this->proxiedClient->requestAsync($method, $uri, $options);
    }

    public function getConfig(?string $option = null)
    {
        return $this->proxiedClient->getConfig($option);
    }
}
