<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client;

class ClientResult
{
    private array $errors;
    private array $responses = [];

    public function __construct(array $errors = [])
    {
        $this->errors = $errors;
    }

    /**
     * @return array<int, \Throwable[]>
     */
    public function getRequestErrors(): array
    {
        return $this->errors;
    }

    public function addRequestError(object $request, \Throwable $error): void
    {
        $this->errors[spl_object_id($request)][] = $error;
    }

    /**
     * @throws \Exception
     */
    public function getRequestResponse(object $request): object
    {
        if (!isset($this->responses[spl_object_id($request)])) {
            throw new \Exception('By some reasons, response was not received properly.');
        }

        return $this->responses[spl_object_id($request)];
    }

    public function addRequestResponse(object $request, object $response): void
    {
        $this->responses[spl_object_id($request)] = $response;
    }
}
