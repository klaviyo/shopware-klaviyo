<?php

namespace Klaviyo\Integration\Job;

use Shopware\Core\Framework\Context;

interface JobSchedulerInterface
{
    public function scheduleJob(Context $context, string $jobType, bool $createdBySchedule = false): void;

    public function isApplicable(Context $context, string $jobType): bool;
}