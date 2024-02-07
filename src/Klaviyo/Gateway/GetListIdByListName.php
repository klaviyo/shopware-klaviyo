<?php

namespace Klaviyo\Integration\Klaviyo\Gateway;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetLists\DTO\ProfilesListInfo;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetLists\GetProfilesListsRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetLists\GetProfilesListsResponse;
use Klaviyo\Integration\Klaviyo\Gateway\Exception\ProfilesListNotFoundException;

class GetListIdByListName implements GetListIdByListNameInterface
{
    private ClientRegistry $clientRegistry;

    public function __construct(ClientRegistry $clientRegistry)
    {
        $this->clientRegistry = $clientRegistry;
    }

    public function execute(string $salesChannelEntityId, string $listId): string
    {
        $request = new GetProfilesListsRequest();
        $clientResult = $this->clientRegistry
            ->getClient($salesChannelEntityId)
            ->sendRequests([$request]);

        /** @var GetProfilesListsResponse $result */
        $result = $clientResult->getRequestResponse($request);
        if (!$result->isSuccess()) {
            throw new ProfilesListNotFoundException(
                \sprintf('Could not get Profiles list from Klaviyo. Reason: %s', $result->getErrorDetails())
            );
        }

        /** @var ProfilesListInfo $list */
        foreach ($result->getLists() as $list) {
            if ($list->getId() === $listId) {
                return $list->getId();
            }
        }

        throw new ProfilesListNotFoundException(
            \sprintf('Profiles list[name: "%s"] was not found', $listId)
        );
    }
}
