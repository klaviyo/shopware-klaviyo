<?php

namespace Klaviyo\Integration\Klaviyo\Client\Exception;

class TranslationException extends ClientException
{
    public function __construct(
        $translatedValue,
        $reason = "",
        \Throwable $previous = null
    ) {
        $message = sprintf('Failed to translate "%s".', get_class($translatedValue));
        if ($reason) {
            $message = sprintf('%s Reason: %s', $message, $reason);
        }

        $this->addToLoggableContext('translatedValue', $translatedValue);

        parent::__construct($message, 0, $previous);
    }

}