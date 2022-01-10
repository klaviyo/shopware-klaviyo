<?php declare(strict_types=1);

namespace Klaviyo\Integration\System\Job;

use Klaviyo\Integration\Entity\Job\JobEntity;
use Shopware\Core\Framework\Uuid\Uuid;

class BackgroundJobFactory
{
    public function create(string $name, string $type): JobEntity
    {
        $job = new JobEntity();
        $job->setId(Uuid::randomHex());
        $job->setStatus(JobEntity::STATUS_PENDING);
        $job->setName($name);
        $job->setType($type);

        return $job;
    }
}
