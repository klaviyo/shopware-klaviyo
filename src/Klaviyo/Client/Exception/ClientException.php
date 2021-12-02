<?php

namespace Klaviyo\Integration\Klaviyo\Client\Exception;

use Klaviyo\Integration\Utils\Logger\LoggableContextAwareExceptionInterface;
use Klaviyo\Integration\Utils\Logger\LoggableContextExceptionTrait;

class ClientException extends \Exception implements LoggableContextAwareExceptionInterface
{
    use LoggableContextExceptionTrait;
}