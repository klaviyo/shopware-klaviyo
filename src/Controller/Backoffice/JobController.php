<?php

namespace Klaviyo\Integration\Controller\Backoffice;

use Klaviyo\Integration\Exception\JobAlreadyRunningException;
use Klaviyo\Integration\Exception\JobAlreadyScheduledException;
use Klaviyo\Integration\Model\UseCase\Operation\FullCustomerOrderSyncOperation;
use Klaviyo\Integration\Model\UseCase\Operation\FullCustomerSubsSyncOperation;
use Klaviyo\Integration\Model\UseCase\ScheduleBackgroundJob;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;

/**
 * @RouteScope(scopes={"api"})
 * @Route(
 *     "/api/_action/klaviyo"
 * )
 */
class JobController
{
    private ScheduleBackgroundJob $scheduleBackgroundJob;
    private SystemConfigService $systemConfigService;

    public function __construct(
        ScheduleBackgroundJob $scheduleBackgroundJob,
        SystemConfigService   $systemConfigService
    ) {
        $this->scheduleBackgroundJob = $scheduleBackgroundJob;
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * @Route(
     *     "/historical-event-tracking/synchronization/schedule",
     *     name="api.action.klaviyo.historical.event.tracking.synchronization.schedule",
     *     methods={"POST"},
     *     requirements={"version"="\d+"}
     * )
     * @param Context $context
     * @return JsonResponse
     */
    public function scheduleHistoricalEventTrackingSynchronizationAction(RequestDataBag $post, Context $context): JsonResponse
    {
        $fromDate = $post->get('fromDate') ?: null;
        $tillDate = $post->get('tillDate') ?: null;
        
        return $this->doScheduleJob(function () use ($context, $fromDate, $tillDate) {
            $this->scheduleBackgroundJob->scheduleFullOrderSyncJob($context, $fromDate, $tillDate);
            $isCustomerSyncOn = $this->systemConfigService->getBool(
                FullCustomerOrderSyncOperation::IS_ENABLED_WITHOUT_ORDERS_SYNC
            );

            if ($isCustomerSyncOn) {
                $this->scheduleBackgroundJob->scheduleFullCustomerOrderSyncJob($context, $fromDate, $tillDate);
            }
        });
    }

    /**
     * @Route(
     *     "/subscribers/synchronization/schedule",
     *     name="api.action.klaviyo.subscribers.synchronization.schedule",
     *     methods={"POST"},
     *     requirements={"version"="\d+"}
     * )
     * @param Context $context
     * @return JsonResponse
     */
    public function scheduleSubscribersSynchronizationAction(Context $context): JsonResponse
    {
        return $this->doScheduleJob(function () use ($context) {
            $this->scheduleBackgroundJob->scheduleFullSubscriberSyncJob($context);
            $isCustomerSyncOn = $this->systemConfigService->getBool(
                FullCustomerSubsSyncOperation::IS_ENABLED_WITHOUT_SUBSCRIBERS_SYNC
            );

            if ($isCustomerSyncOn) {
                $this->scheduleBackgroundJob->scheduleFullCustomerSubsSyncJob($context);
            }
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
