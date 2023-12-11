<?php

namespace Klaviyo\Integration\Controller\Backoffice;

use Klaviyo\Integration\Klaviyo\Promotion\PromotionsExporter;
use Klaviyo\Integration\Model\Response\KlaviyoBinaryFileResponse;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class PromotionsExportController
{
    private PromotionsExporter $promotionsExporter;

    public function __construct(PromotionsExporter $promotionsExporter)
    {
        $this->promotionsExporter = $promotionsExporter;
    }

    /**
     * @RouteScope(scopes={"administration"})
     * @Route(
     *     "/api/klaviyo/integration/promotion/export", defaults={"auth_required"=false}
     * )
     */
    public function export(Context $context, Request $request): KlaviyoBinaryFileResponse
    {
        $promotionId = $request->query->get('id');
        $fileObject = $this->promotionsExporter->exportToCSV($context, null, $promotionId);

        $response = new KlaviyoBinaryFileResponse($fileObject);
        $response->deleteFileAfterSend(true);

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Cache-Control', 'private');

        // Set content disposition inline of the file
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'promotions.csv'
        );

        return $response;
    }
}
