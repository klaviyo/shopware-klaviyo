<?php
declare(strict_types=1);

namespace Klaviyo\Integration\Utils;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class LocaleCodeProducer
{
    private const DEFAULT_LANGUAGE_LOCALE = 'en-GB';

    private EntityRepository $languageRepository;

    public function __construct(
        EntityRepository $languageRepository
    ) {
        $this->languageRepository = $languageRepository;
    }

    public function getLocaleCodeFromContext(string $languageId, Context $context): string
    {
        $criteria = new Criteria([$languageId]);
        $criteria->addAssociation('locale');
        $criteria->setLimit(1);

        $language = $this->languageRepository->search($criteria, $context)->first();

        if ($language === null) {
            return self::DEFAULT_LANGUAGE_LOCALE;
        }

        $locale = $language->getLocale();

        if (!$locale) {
            return self::DEFAULT_LANGUAGE_LOCALE;
        }

        return $locale->getCode();
    }

    /**
     * Locale string for a language id, or null when lookup fails or the code is empty.
     * Prefer {@see getLocaleCodeFromContext} when a non-empty string must always be returned.
     */
    public function getOptionalLocaleCodeForLanguage(string $languageId, Context $context): ?string
    {
        try {
            $code = $this->getLocaleCodeFromContext($languageId, $context);

            return $code !== '' ? $code : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
