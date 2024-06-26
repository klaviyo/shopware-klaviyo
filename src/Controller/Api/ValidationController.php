<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Controller\Api;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Account\GetAccountRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetLists\GetProfilesListsRequest;
use Klaviyo\Integration\Klaviyo\Client\ClientInterface;
use Klaviyo\Integration\Klaviyo\Gateway\ClientRegistry;
use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_routeScope"={"api"}})
 */
class ValidationController extends AbstractController
{
    private ClientRegistry $clientRegistry;
    private array $profileLists = [];
    private ClientInterface $client;

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
        $listId = $post->get('listId');

        if (empty($listId) || empty($publicKey) || empty($privateKey)) {
            return new JsonResponse(['invalid_parameters' => true], Response::HTTP_OK);
        }

        $this->client = $client = $this->clientRegistry->getClientByKeys($privateKey, $publicKey);

        $accountRequest = new GetAccountRequest($publicKey);
        $responses = $client->sendRequests([$accountRequest]);

        if (!empty($responses->getRequestErrors())) {
            return new JsonResponse(['general_error' => true], Response::HTTP_OK);
        }

        try {
            $response = $this->getAllProfileLists(new GetProfilesListsRequest(null, $listId));
            $accountResponse = $responses->getRequestResponse($accountRequest);
        } catch (\Exception $e) {
            return new JsonResponse(['general_error' => true], Response::HTTP_OK);
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

        foreach ($this->profileLists as $list) {
            if ($list['value'] === $listId) {
                return new JsonResponse(['success' => true], Response::HTTP_OK);
            }
        }

        return $response;
    }

    /**
     * @Route("/api/_action/od-get-subscriber-lists", name="api.action.od_get_subscriber_lists", methods={"POST"}, defaults={"auth_required"=false})
     *
     * @throws /Exception
     */
    public function getSubscriberListsAvailable(RequestDataBag $post): JsonResponse
    {
        $publicKey = $post->get('publicKey');
        $privateKey = $post->get('privateKey');

        if (empty($publicKey) || empty($privateKey)) {
            return new JsonResponse(['invalid_parameters' => true], Response::HTTP_OK);
        }

        $this->client = $this->clientRegistry->getClientByKeys($privateKey, $publicKey);
        $result = $this->getAllProfileLists(new GetProfilesListsRequest());

        if (empty($this->profileLists)) {
            return $result;
        }

        return new JsonResponse(['success' => true, 'data' => $this->profileLists,
        ], Response::HTTP_OK);
    }

    /**
     * @Route("/api/_action/od-list-id-validate", name="api.action.od_list_id_validate", methods={"POST"}, defaults={"auth_required"=false})
     *
     * @throws /Exception
     */
    public function getSubscriberListsByIdAvailable(RequestDataBag $post): JsonResponse
    {
        $publicKey = $post->get('publicKey');
        $privateKey = $post->get('privateKey');
        $searchedListId = $post->get('listId');

        if (empty($publicKey) || empty($privateKey)) {
            return new JsonResponse(['invalid_parameters' => true], Response::HTTP_OK);
        }

        $this->client = $this->clientRegistry->getClientByKeys($privateKey, $publicKey);
        $result = $this->getAllProfileLists(new GetProfilesListsRequest(null, $searchedListId));

        if (empty($this->profileLists)) {
            return $result;
        }

        return new JsonResponse(['success' => true, 'data' => $this->profileLists,], Response::HTTP_OK);
    }

    private function parseListNamesFromResponse($response): void
    {
        foreach ($response->getLists()->getElements() as $e) {
            $this->profileLists[] = [
                'value' => $e->getId(),
                'label' => $e->getName(),
            ];
        }
    }

    private function getAllProfileLists($request): JsonResponse
    {
        $client = $this->client;
        $responses = $client->sendRequests([$request]);

        if (!empty($responses->getRequestErrors())) {
            return new JsonResponse(['general_error' => true], Response::HTTP_OK);
        }

        try {
            $response = $responses->getRequestResponse($request);
        } catch (\Exception $e) {
            return new JsonResponse(['general_error' => true], Response::HTTP_OK);
        }

        if (!$response->isSuccess()) {
            return new JsonResponse(
                ['incorrect_credentials' => true, 'incorrect_credentials_message' => $response->getErrorDetails()],
                Response::HTTP_OK
            );
        }

        $this->parseListNamesFromResponse($response);

        if ($response->getNextPageUrl()) {
            $this->getAllProfileLists(new GetProfilesListsRequest($response->getNextPageUrl()));
        }

        return new JsonResponse(['incorrect_list' => true], Response::HTTP_OK);
    }
}
