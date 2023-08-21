<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Controller\Api;

use Exception;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetLists\GetProfilesListsRequest;
use Klaviyo\Integration\Klaviyo\Client\ClientResult;
use Klaviyo\Integration\Klaviyo\Gateway\ClientRegistry;
use Klaviyo\Integration\Klaviyo\Gateway\Exception\ProfilesListNotFoundException;
use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Test\TestDefaults;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;

#[Route(defaults: ['_routeScope' => ['api']])]
class ValidationController extends AbstractController
{
    private ClientRegistry $clientRegistry;

    public function __construct(ClientRegistry $clientRegistry)
    {
        $this->clientRegistry = $clientRegistry;
    }

    #[Route(path:"/api/_action/od-api-key-validate", name:"api.action.od_api_key_validate", defaults: ['auth_required' => false], methods:["POST"])]
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
        $responses = $client->sendRequests([$request]);
        if (!empty($responses->getRequestErrors())) {
            return new JsonResponse(['general_error' => true], Response::HTTP_OK);
        }

        try {
            $response = $responses->getRequestResponse($request);
        } catch (Exception $e) {
            return new JsonResponse(['general_error' => true], Response::HTTP_OK);
        }

        if (!$response->isSuccess()) {
            return new JsonResponse(
                ['incorrect_credentials' => true, 'incorrect_credentials_message' => $response->getErrorDetails()],
                Response::HTTP_OK
            );
        }

        foreach ($response->getLists() as $list) {
            if ($list->getName() === $listName) {
                return new JsonResponse(['success' => true], Response::HTTP_OK);
            }
        }

        return new JsonResponse(['incorrect_list' => true], Response::HTTP_OK);
    }

    #[Route(path:"/api/_action/od-get-subscriber-lists", name:"api.action.od_get_subscriber_lists", defaults: ['auth_required' => false], methods:["POST"])]
    public function getSubscriberListsAvailable(RequestDataBag $post)
    {
        $publicKey = $post->get('publicKey');
        $privateKey = $post->get('privateKey');

        if (empty($publicKey) || empty($privateKey)) {
            return new JsonResponse(['invalid_parameters' => true], Response::HTTP_OK);
        }

        $client = $this->clientRegistry->getClientByKeys($privateKey, $publicKey);
        $request = new GetProfilesListsRequest();
        $clientResult = $client->sendRequests([$request]);
        $result = $clientResult->getRequestResponse($request);

        $data = $this->parseListNamesFromResponse($result);

        if (empty($data)) {
            return new JsonResponse(['incorrect_list' => true], Response::HTTP_OK);
        }

        return new JsonResponse(['success' => true, 'data' =>
            $data
        ], Response::HTTP_OK);
    }

    private function parseListNamesFromResponse($response): array
    {
        $data = [];

        foreach ($response->getLists()->getElements() as $e) {
            $data[] = [
                'value' => $e->getName(),
                'label' => $e->getName()
            ];
        }

        return $data;
    }
}
