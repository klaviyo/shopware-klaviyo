<?php declare(strict_types=1);

namespace Od\Scheduler\Controller\Administration;

use Od\Scheduler\Model\JobScheduler;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Routing\Exception\InvalidRequestParameterException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"api"})
 */
class RescheduleController extends AbstractController
{
    private JobScheduler $jobScheduler;

    public function __construct(JobScheduler $jobScheduler)
    {
        $this->jobScheduler = $jobScheduler;
    }

    /**
     * @Route(
     *     "/api/_action/od-job/reschedule",
     *     name="api.od.scheduler.od.job.event.reschedule",
     *     methods={"POST"}
     * )
     * @return JsonResponse
     */
    public function rescheduleAction(Request $request)
    {
        $jobId = $request->request->get('params')['jobId'] ?? null;
        if (!\is_string($jobId)) {
            throw new InvalidRequestParameterException('jobId');
        }

        $this->jobScheduler->reschedule($jobId);

        return new JsonResponse();
    }
}
