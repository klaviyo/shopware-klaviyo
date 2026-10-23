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
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['api'], '_acl' => ['klaviyo_job_event:create', 'klaviyo_job_event:update', 'klaviyo_job_event:delete', 'klaviyo_job_event:read', 'klaviyo_checkout_mapping:read', 'klaviyo_checkout_mapping:create']])]
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

    #[Route(path:"/api/_action/klaviyo/historical-event-tracking/synchronization/schedule", name:"api.action.klaviyo.historical.event.tracking.synchronization.schedule", requirements: ['version' => '\d+'], methods:["POST"])]
    public function scheduleHistoricalEventTrackingSynchronizationAction(RequestDataBag $post, Context $context): JsonResponse
    {
        $fromDate = $post->get('fromDate') ?: null;
        $tillDate = $post->get('tillDate') ?: null;

        $dateRangeErrorCode = $this->validateDateRange($fromDate, $tillDate);
        if ($dateRangeErrorCode !== null) {
            return new JsonResponse(['isScheduled' => false, 'errorCode' => $dateRangeErrorCode], 200);
        }

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

    #[Route(path:"/api/_action/klaviyo/subscribers/synchronization/schedule", name:"api.action.klaviyo.subscribers.synchronization.schedule", requirements: ['version' => '\d+'], methods:["POST"])]
    public function scheduleSubscribersSynchronizationAction(RequestDataBag $post, Context $context): JsonResponse
    {
        $fromDate = $post->get('fromDate') ?: null;
        $tillDate = $post->get('tillDate') ?: null;

        $dateRangeErrorCode = $this->validateDateRange($fromDate, $tillDate);
        if ($dateRangeErrorCode !== null) {
            return new JsonResponse(['isScheduled' => false, 'errorCode' => $dateRangeErrorCode], 200);
        }

        return $this->doScheduleJob(function () use ($context, $fromDate, $tillDate) {
            $this->scheduleBackgroundJob->scheduleFullSubscriberSyncJob($context, $fromDate, $tillDate);
            $isCustomerSyncOn = $this->systemConfigService->getBool(
                FullCustomerSubsSyncOperation::IS_ENABLED_WITHOUT_SUBSCRIBERS_SYNC
            );

            if ($isCustomerSyncOn) {
                $this->scheduleBackgroundJob->scheduleFullCustomerSubsSyncJob($context, $fromDate, $tillDate);
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

    /**
     * The parameters are intentionally untyped: they come straight from the request body, so an API
     * client can send an array or object instead of a string.
     *
     * @param mixed $fromDate
     * @param mixed $tillDate
     * @return string|null the errorCode to return to the caller, or null if the range is valid
     */
    private function validateDateRange($fromDate, $tillDate): ?string
    {
        if (!$this->isValidDate($fromDate) || !$this->isValidDate($tillDate)) {
            return 'INVALID_DATE_FORMAT';
        }

        if ($fromDate !== null && $tillDate !== null && new \DateTime($fromDate) > new \DateTime($tillDate)) {
            return 'INVALID_DATE_RANGE';
        }

        return null;
    }

    /**
     * @param mixed $date
     */
    private function isValidDate($date): bool
    {
        if ($date === null) {
            return true;
        }

        if (!is_string($date)) {
            return false;
        }

        // \DateTime's constructor is lenient enough to silently normalize a calendar-invalid date
        // (e.g. "2024-02-30" rolls over to "2024-03-01") instead of throwing, so checkdate() first
        // guards against that; the try/catch below still catches a malformed time component after
        // an otherwise-valid date prefix.
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $date, $matches)) {
            return false;
        }

        [, $year, $month, $day] = $matches;

        if (!checkdate((int) $month, (int) $day, (int) $year)) {
            return false;
        }

        try {
            new \DateTime($date);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }
}
