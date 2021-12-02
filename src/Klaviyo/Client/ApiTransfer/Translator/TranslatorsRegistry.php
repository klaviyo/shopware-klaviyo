<?php

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Translator;

class TranslatorsRegistry
{
    /** @var array|ApiTransferMessageTranslatorInterface[] */
    private array $translators = [];

    public function addTranslator(ApiTransferMessageTranslatorInterface $translator)
    {
        $this->translators[] = $translator;
    }

    public function getTranslatorForRequest(object $request): ?ApiTransferMessageTranslatorInterface
    {
        foreach ($this->translators as $translator) {
            if ($translator->isSupport($request)) {
                return $translator;
            }
        }

        return null;
    }
}