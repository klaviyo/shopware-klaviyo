<?php

namespace Klaviyo\Integration\Controller\Backoffice;

use Klaviyo\Integration\Klaviyo\Promotion\PromotionsExporter;
use Klaviyo\Integration\Model\Response\KlaviyoBinaryFileResponse;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class PromotionsExportController
{
    private PromotionsExporter $promotionsExporter;

    public function __construct(PromotionsExporter $promotionsExporter)
    {
        $this->promotionsExporter = $promotionsExporter;
    }

    #[Route(path:"/api/klaviyo/integration/promotion/export", defaults: ['auth_required' => false], methods: ['GET'])]
    public function export(Context $context)
    {
        $fileObject = $this->promotionsExporter->exportToCSV($context);

        $response = new KlaviyoBinaryFileResponse($fileObject);
        $response->deleteFileAfterSend(true);

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Cache-Control','private');

        // Set content disposition inline of the file
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'promotions.csv'
        );

        return $response;
    }
}
