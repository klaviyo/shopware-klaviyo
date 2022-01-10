<?php

namespace Klaviyo\Integration\System\Job;

use Klaviyo\Integration\Entity\Job\JobEntity;
use Shopware\Core\Framework\Context;
use Symfony\Component\Messenger\Envelope;

interface SchedulerInterface
{
    public function scheduleJob(Context $context, JobEntity $job, Envelope $message): JobEntity;
}
