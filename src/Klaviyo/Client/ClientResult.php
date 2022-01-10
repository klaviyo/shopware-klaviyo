<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client;

class ClientResult
{
//    private array $responses;
    private array $errors;

    public function __construct(
//        array $responses = [],
//        array $errors = []
    ) {
//        foreach ($errors as $error) {
//            if (!$error instanceof \Throwable) {
//                throw new \LogicException(sprintf('Error %s must be of \Throwable type.', get_class($error)));
//            }
//        }
//
//        $this->responses = $responses;
        $this->errors = [];
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

    public function getResponses(): \Traversable
    {
        yield $this->responses;
    }
}
