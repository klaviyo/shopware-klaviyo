<?php declare(strict_types=1);

namespace Klaviyo\Integration\Async\Message;

use Od\Scheduler\Async\JobMessageInterface;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;

abstract class AbstractBasicMessage implements JobMessageInterface
{
    private string $jobId;
    protected string $name;
    protected static string $defaultName = 'Unnamed Operation';
    protected ?Context $context;

    public function __construct(
        string $jobId,
        ?string $name = null,
        ?Context $context = null
    ) {
        $this->jobId = $jobId;
        $this->name = $name ?? static::$defaultName;
        $this->setContext($context);
    }

    protected function setContext(?Context $context)
    {
        // All values should be sent to nosto with the default currency and language
        if ($context === null) {
            $this->context = new Context(new SystemSource());
            return;
        }

        // All values should be sent to nosto with the default currency and language
        if ($context->getLanguageId() !== Defaults::LANGUAGE_SYSTEM || $context->getCurrencyId() !== Defaults::CURRENCY) {
            $this->context = new Context(
                $context->getSource(),
                $context->getRuleIds(),
                Defaults::CURRENCY,
                [Defaults::LANGUAGE_SYSTEM],
                $context->getVersionId(),
                $context->getCurrencyFactor(),
                $context->considerInheritance(),
                $context->getTaxState(),
                $context->getRounding()
            );
        } else {
            $this->context = $context;
        }
    }

    public function getContext()
    {
        return $this->context ?: new Context(new SystemSource());
    }

    public function getJobId(): string
    {
        return $this->jobId;
    }

    public function getJobName(): string
    {
        return $this->name;
    }
}
