<?php

namespace Klaviyo\Integration\Utils\Logger;

interface LoggableContextAwareExceptionInterface
{
    public function getLoggableContext(): array;
}