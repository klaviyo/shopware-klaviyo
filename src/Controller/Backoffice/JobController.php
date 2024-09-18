<?php

namespace Klaviyo\Integration\Controller\Backoffice;

use Klaviyo\Integration\Exception\JobAlreadyRunningException;
use Klaviyo\Integration\Exception\JobAlreadyScheduledException;
use Klaviyo\Integration\Model\UseCase\ScheduleBackgroundJob;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['api'], '_acl' => ['klaviyo_job_event:create', 'klaviyo_job_event:update', 'klaviyo_job_event:delete', 'klaviyo_job_event:read', 'klaviyo_checkout_mapping:read', 'klaviyo_checkout_mapping:create']])]
class JobController
{
    private ScheduleBackgroundJob $scheduleBackgroundJob;

    public function __construct(ScheduleBackgroundJob $scheduleBackgroundJob)
    {
        $this->scheduleBackgroundJob = $scheduleBackgroundJob;
    }

    #[Route(path:"/api/_action/klaviyo/historical-event-tracking/synchronization/schedule", name:"api.action.klaviyo.historical.event.tracking.synchronization.schedule", requirements: ['version' => '\d+'], methods:["POST"])]
    public function scheduleHistoricalEventTrackingSynchronizationAction(Context $context): JsonResponse
    {
        return $this->doScheduleJob(function () use ($context) {
            $this->scheduleBackgroundJob->scheduleFullOrderSyncJob($context);
        });
    }

    #[Route(path:"/api/_action/klaviyo/subscribers/synchronization/schedule", name:"api.action.klaviyo.subscribers.synchronization.schedule", requirements: ['version' => '\d+'], methods:["POST"])]
    public function scheduleSubscribersSynchronizationAction(Context $context): JsonResponse
    {
        return $this->doScheduleJob(function () use ($context) {
            $this->scheduleBackgroundJob->scheduleFullSubscriberSyncJob($context);
        });
    }

    private function doScheduleJob(\Closure $scheduler): JsonResponse
    {
        try {
            $scheduler();
        } catch (JobAlreadyRunningException $e) {
            return new JsonResponse([
                'isScheduled' => false,
                'errorCode' => 'SYNCHRONIZATION_IS_ALREADY_RUNNING'
            ], 200);
        } catch (JobAlreadyScheduledException $e) {
            return new JsonResponse([
                'isScheduled' => false,
                'errorCode' => 'SYNCHRONIZATION_IS_ALREADY_SCHEDULED'
            ], 200);
        }

        return new JsonResponse(['isScheduled' => true, 'errorCode' => ''], 200);
    }
}
