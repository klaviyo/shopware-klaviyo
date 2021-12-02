<?php

namespace Klaviyo\Integration\Controller\Backoffice;

use Klaviyo\Integration\Entity\Helper\JobHelper;
use Klaviyo\Integration\Entity\Job\JobEntity;
use Klaviyo\Integration\Job\Exception\JobIsAlreadyRunningException;
use Klaviyo\Integration\Job\JobSchedulerInterface;
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
    private JobHelper $jobHelper;
    private JobSchedulerInterface $jobScheduler;
    private LoggerInterface $logger;

    public function __construct(
        JobHelper $jobHelper,
        JobSchedulerInterface $jobScheduler,
        LoggerInterface $logger
    ) {
        $this->jobHelper = $jobHelper;
        $this->jobScheduler = $jobScheduler;
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
            JobEntity::HISTORICAL_EVENTS_SYNCHRONIZATION_TYPE
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
            JobEntity::SUBSCRIBERS_SYNCHRONIZATION_TYPE
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
        return $this->scheduleSynchronization($context, JobEntity::HISTORICAL_EVENTS_SYNCHRONIZATION_TYPE);
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
        return $this->scheduleSynchronization($context, JobEntity::SUBSCRIBERS_SYNCHRONIZATION_TYPE);
    }

    private function getJobSynchronizationStatusesJsonResponse(Context $context, string $jobType): JsonResponse
    {
        try {
            $lastSuccessJob = $this->jobHelper->getLastSuccessJob($context, $jobType);
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

    private function scheduleSynchronization(Context $context, string $jobType): JsonResponse
    {
        $errorCode = '';
        $isScheduled = false;
        $responseCode = 200;
        try {
            $this->jobScheduler->scheduleJob($context, $jobType);
            $isScheduled = true;
        } catch (JobIsAlreadyRunningException $exception) {
            $errorCode = 'SYNCHRONIZATION_IS_ALREADY_RUNNING';
        } catch (\Throwable $exception) {
            $errorCode = 'SYNCHRONIZATION_IS_FAILED';
            $responseCode = 503;
            $this->logger
                ->error('Synchronization schedule failed', ['exception' => $exception]);
        }

        return new JsonResponse(['isScheduled' => $isScheduled, 'errorCode' => $errorCode], $responseCode);
    }
}