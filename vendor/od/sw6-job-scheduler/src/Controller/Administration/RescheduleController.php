<?php declare(strict_types=1);

namespace Od\Scheduler\Controller\Administration;

use Od\Scheduler\Model\JobScheduler;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Routing\RoutingException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class RescheduleController extends AbstractController
{
    public function __construct(
        private readonly JobScheduler $jobScheduler
    ){}

    #[Route(path:"/api/_action/od-job/reschedule", name:"api.od.scheduler.od.job.event.reschedule",defaults: ['auth_required' => false, 'routeScope' => ['api']], options:["seo"=>"false"], methods:["POST"])]
    public function rescheduleAction(Request $request)
    {
        $jobId = $request->request->all('params')['jobId'] ?? null;
        if (!\is_string($jobId)) {
            throw RoutingException::invalidRequestParameter('jobId');
        }

        $this->jobScheduler->reschedule($jobId);

        return new JsonResponse();
    }
}
