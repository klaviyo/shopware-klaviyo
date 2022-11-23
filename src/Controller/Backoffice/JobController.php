<?php

namespace Klaviyo\Integration\Controller\Backoffice;

use Klaviyo\Integration\Exception\JobAlreadyRunningException;
use Klaviyo\Integration\Exception\JobAlreadyScheduledException;
use Klaviyo\Integration\Model\UseCase\ScheduleBackgroundJob;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"api"})
 * @Route(
 *     "/api/_action/klaviyo"
 * )
 */
class JobController
{
    private ScheduleBackgroundJob $scheduleBackgroundJob;

    public function __construct(ScheduleBackgroundJob $scheduleBackgroundJob)
    {
        $this->scheduleBackgroundJob = $scheduleBackgroundJob;
    }

    /**
     * @Route(
     *     "/historical-event-tracking/synchronization/schedule",
     *     name="api.action.klaviyo.historical.event.tracking.synchronization.schedule",
     *     methods={"POST"},
     *     requirements={"version"="\d+"}
     * )
     * @return JsonResponse
     */
    public function scheduleHistoricalEventTrackingSynchronizationAction(Context $context)
    {
        return $this->doScheduleJob(function () use ($context) {
            $this->scheduleBackgroundJob->scheduleFullOrderSyncJob($context);
        });
    }

    /**
     * @Route(
     *     "/subscribers/synchronization/schedule",
     *     name="api.action.klaviyo.subscribers.synchronization.schedule",
     *     methods={"POST"},
     *     requirements={"version"="\d+"}
     * )
     * @return JsonResponse
     */
    public function scheduleSubscribersSynchronizationAction(Context $context)
    {
        return $this->doScheduleJob(function () use ($context) {
            $this->scheduleBackgroundJob->scheduleFullSubscriberSyncJob($context);
        });
    }

    private function doScheduleJob(\Closure $scheduler)
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
