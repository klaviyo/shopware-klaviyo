<?php

namespace Klaviyo\Integration\Job;

use Klaviyo\Integration\Entity\Job\JobEntity;
use Shopware\Core\Framework\Context;

interface JobProcessorInterface
{
    /**
     * @param Context $context
     * @param JobEntity $job
     *
     * @throw \Throwable
     */
    public function process(Context $context, JobEntity $job): void;

    public function isApplicable(Context $context, JobEntity $job): bool;
}