<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Controller\Api;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Account\GetAccountRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetLists\GetProfilesListsRequest;
use Klaviyo\Integration\Klaviyo\Gateway\ClientRegistry;
use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(scopes={"api"})
 */
class ValidationController extends AbstractController
{
    private ClientRegistry $clientRegistry;

    public function __construct(ClientRegistry $clientRegistry)
    {
        $this->clientRegistry = $clientRegistry;
    }

    /**
     * @OA\Post(
     *     path="/_action/od-api-key-validate",
     *     summary="Validate api keys for Klavio",
     *     description="Validates if the given api keys are valid for Klavio",
     *     operationId="od-api-validate",
     *     tags={"Admin API", "Od Validation"},
     *     @OA\RequestBody(
     *         required=true
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Returns a json response file with validation info."
     *     )
     * )
     * @Route("/api/_action/od-api-key-validate", name="api.action.od_api_key_validate", methods={"POST"}, defaults={"auth_required"=false})
     */
    public function validate(RequestDataBag $post): JsonResponse
    {
        $publicKey = $post->get('publicKey');
        $privateKey = $post->get('privateKey');
        $listName = $post->get('listName');

        if (empty($listName) || empty($publicKey) || empty($privateKey)) {
            return new JsonResponse(['invalid_parameters' => true], Response::HTTP_OK);
        }

        $client = $this->clientRegistry->getClientByKeys($privateKey, $publicKey);
        $request = new GetProfilesListsRequest();
        $accountRequest = new GetAccountRequest($publicKey);
        $responses = $client->sendRequests([$request, $accountRequest]);

        if (!empty($responses->getRequestErrors())) {
            return new JsonResponse(['general_error' => true], Response::HTTP_OK);
        }

        try {
            $response = $responses->getRequestResponse($request);
            $accountResponse = $responses->getRequestResponse($accountRequest);
        } catch (\Exception $e) {
            return new JsonResponse(['general_error' => true], Response::HTTP_OK);
        }

        if (!$response->isSuccess()) {
            return new JsonResponse(
                ['incorrect_credentials' => true, 'incorrect_credentials_message' => $response->getErrorDetails()],
                Response::HTTP_OK
            );
        }

        if (!$accountResponse->isSuccess()) {
            return new JsonResponse(
                [
                    'incorrect_credentials' => true,
                    'incorrect_credentials_message' => $accountResponse->getErrorDetails(),
                ],
                Response::HTTP_OK
            );
        }

        foreach ($response->getLists() as $list) {
            if ($list->getName() === $listName) {
                return new JsonResponse(['success' => true], Response::HTTP_OK);
            }
        }

        if ($accountResponse->getAccountIdFromKlaviyo() === $publicKey) {
            return new JsonResponse(['success' => true], Response::HTTP_OK);
        }

        return new JsonResponse(['incorrect_list' => true], Response::HTTP_OK);
    }
}
