<?php

namespace Klaviyo\Integration\Controller\Backoffice;

use Klaviyo\Integration\Klaviyo\Promotion\PromotionsExporter;
use Klaviyo\Integration\Model\Response\KlaviyoBinaryFileResponse;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\Acl;
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
     * The Acl annotation below is required for Shopware 6.4.4 - 6.4.10, whose AclAnnotationValidator
     * ignores an "_acl" route default; 6.4.11+ reads the default. Both carry the same privileges.
     *
     * @RouteScope(scopes={"administration"})
     * @Acl({"promotion:read", "promotion_individual_code:read"})
     * @Route(
     *     "/api/klaviyo/integration/promotion/export",
     *     name="api.klaviyo.integration.promotion.export",
     *     defaults={"_acl"={"promotion:read", "promotion_individual_code:read"}},
     *     methods={"GET"}
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
