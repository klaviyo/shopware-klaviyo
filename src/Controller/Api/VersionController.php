<?php
declare(strict_types=1);

namespace Klaviyo\Integration\Controller\Api;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Klaviyo\Integration\klavi_overd;
use Shopware\Core\Framework\Plugin\PluginEntity;

#[Route(defaults: ['_routeScope' => ['api']])]
class VersionController extends AbstractController
{
    private EntityRepository $pluginRepository;

    public function __construct(
        EntityRepository $pluginRepository
    ) {
        $this->pluginRepository = $pluginRepository;
    }


    #[Route(path:"/api/klaviyo/version", name:"api.klaviyo.version", defaults: ['auth_required' => false], methods:["get"])]
    public function version(Context $context): JsonResponse
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('baseClass', klavi_overd::class));

        /** @var PluginEntity|null $plugin */
        $plugin = $this->pluginRepository->search($criteria, $context)->first();

        if ($plugin === null) {
            return new JsonResponse(['Klaviyo plugin was not found'], Response::HTTP_OK);
        }

        $pluginVersion = $plugin->getVersion();

        return new JsonResponse(['version' => $pluginVersion], Response::HTTP_OK);
    }
}
