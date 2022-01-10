<?php

namespace Klaviyo\Integration\Controller\Backoffice;

use Klaviyo\Integration\Entity\Job\JobEntity;
use Klaviyo\Integration\Model\UseCase\ScheduleBackgroundJob;
use Klaviyo\Integration\System\Job\JobHelper;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"api"})
 * @Route(
 *     "api/klaviyo/integration/job"
 * )
 */
class JobController
{
    private ScheduleBackgroundJob $scheduleBackgroundJob;
    private JobHelper $jobHelper;
    private LoggerInterface $logger;

    public function __construct(
        ScheduleBackgroundJob $scheduleBackgroundJob,
        JobHelper $jobHelper,
        LoggerInterface $logger
    ) {
        $this->scheduleBackgroundJob = $scheduleBackgroundJob;
        $this->jobHelper = $jobHelper;
        $this->logger = $logger;
    }

    /**
     * @Route(
     *     "/_action/historical-event-tracking/synchronization/get_status",
     *     name="api.klaviyo.integration.historical.event.tracking.synchronization.status",
     *     methods={"GET"},
     *     requirements={"version"="\d+"}
     * )
     * @return JsonResponse
     */
    public function getHistoricalEventTrackingSynchronizationStatusAction(Context $context)
    {
        return $this->getJobSynchronizationStatusesJsonResponse(
            $context,
            JobEntity::TYPE_FULL_ORDERS_SYNC
        );
    }

    /**
     * @Route(
     *     "/_action/subscribers/synchronization/get_status",
     *     name="api.klaviyo.integration.subscribers.synchronization.status",
     *     methods={"GET"},
     *     requirements={"version"="\d+"}
     * )
     * @return JsonResponse
     */
    public function getSubscribersSynchronizationStatusAction(Context $context)
    {
        return $this->getJobSynchronizationStatusesJsonResponse(
            $context,
            JobEntity::TYPE_FULL_SUBSCRIBER_SYNC
        );
    }

    /**
     * @Route(
     *     "/_action/historical-event-tracking/synchronization/schedule",
     *     name="api.klaviyo.integration.historical.event.tracking.synchronization.schedule",
     *     methods={"POST"},
     *     requirements={"version"="\d+"}
     * )
     * @return JsonResponse
     */
    public function scheduleHistoricalEventTrackingSynchronizationAction(Context $context)
    {
        $this->scheduleBackgroundJob->scheduleFullOrderSyncJob($context);

        // TODO: add try/catch block and add specific exceptions (already running etc.)

        return new JsonResponse(['isScheduled' => true, 'errorCode' => ''], 200);
    }

    /**
     * @Route(
     *     "/_action/subscribers/synchronization/schedule",
     *     name="api.klaviyo.integration.subscribers.synchronization.schedule",
     *     methods={"POST"},
     *     requirements={"version"="\d+"}
     * )
     * @return JsonResponse
     */
    public function scheduleSubscribersSynchronizationAction(Context $context)
    {
        $this->scheduleBackgroundJob->scheduleFullSubscriberSyncJob($context);

        // TODO: add try/catch block and add specific exceptions (already running etc.)

        return new JsonResponse(['isScheduled' => true, 'errorCode' => ''], 200);
    }

    private function getJobSynchronizationStatusesJsonResponse(Context $context, string $jobType): JsonResponse
    {
        try {
            $lastSuccessJob = $this->jobHelper->getLastSucceedJob($context, $jobType);
            $lastJob = $this->jobHelper->getLastJob($context, $jobType);

            return new JsonResponse(
                [
                    'lastSuccessJob' => $lastSuccessJob,
                    'lastJob' => $lastJob
                ]
            );
        } catch (\Throwable $exception) {
            $this->logger->error(
                'Exception happened during fetch of the synchronization status',
                [
                    'exception' => $exception
                ]
            );

            throw new HttpException(500, 'Internal server error');
        }
    }
}
