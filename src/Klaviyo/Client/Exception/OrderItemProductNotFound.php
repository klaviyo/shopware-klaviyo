<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\Exception;

class OrderItemProductNotFound extends \Exception
{
    public function __construct(string $productId, $message = "", $code = 0, \Throwable $previous = null)
    {
        if ($message === '') {
            $message = \sprintf('Order Line Item Product [id: %s] was not found.', $productId);
        }

        parent::__construct($message, $code, $previous);
    }
}
