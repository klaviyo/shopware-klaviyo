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
}
