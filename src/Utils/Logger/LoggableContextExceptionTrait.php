<?php

namespace Klaviyo\Integration\Utils\Logger;

trait LoggableContextExceptionTrait
{
    private $loggableContext = [];

    public function addToLoggableContext(string $key, $value)
    {
        $this->loggableContext[$key] = $value;
    }

    public function getLoggableContext(): array
    {
        return $this->loggableContext;
    }
}