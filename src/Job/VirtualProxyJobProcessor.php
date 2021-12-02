<?php

namespace Klaviyo\Integration\Job;

use Klaviyo\Integration\Entity\Job\JobEntity;
use Shopware\Core\Framework\Context;

class VirtualProxyJobProcessor implements JobProcessorInterface
{
    /**
     * @var array|JobProcessorInterface[]
     */
    private array $jobProcessors;

    public function __construct(iterable $jobProcessors)
    {
        foreach ($jobProcessors as $jobProcessor) {
            $this->addJobProcessor($jobProcessor);
        }
    }

    private function addJobProcessor(JobProcessorInterface $jobProcessor)
    {
        $this->jobProcessors[] = $jobProcessor;
    }

    public function process(Context $context, JobEntity $job): void
    {
        $realJobProcessor = $this->getJobProcessor($context, $job);
        if ($realJobProcessor === null) {
            throw new \RuntimeException(
                sprintf('Job processor for job[id: %s, type: %s] was not found', $job->getId(), $job->getType())
            );
        }

        $realJobProcessor->process($context, $job);
    }

    public function isApplicable(Context $context, JobEntity $job): bool
    {
        return null !== $this->getJobProcessor($context, $job);
    }

    private function getJobProcessor(Context $context, JobEntity $job): ?JobProcessorInterface
    {
        foreach ($this->jobProcessors as $jobProcessor) {
            if ($jobProcessor->isApplicable($context, $job)) {
                return $jobProcessor;
            }
        }
        
        return null;
    }
}