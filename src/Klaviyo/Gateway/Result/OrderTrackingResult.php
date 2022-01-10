<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Gateway\Result;

class OrderTrackingResult
{
    private array $orderErrorMap = [];

    public function addFailedOrder(string $orderId, \Throwable $reason): void
    {
        $this->orderErrorMap[$orderId][] = $reason;
    }

    public function getFailedOrdersErrors(): array
    {
        return $this->orderErrorMap;
    }

    public function mergeWith(OrderTrackingResult $trackingResult): OrderTrackingResult
    {
        foreach ($trackingResult->getFailedOrdersErrors() as $orderId => $errorArray) {
            foreach ($errorArray as $error) {
                $this->addFailedOrder($orderId, $error);
            }
        }

        return $this;
    }
}
