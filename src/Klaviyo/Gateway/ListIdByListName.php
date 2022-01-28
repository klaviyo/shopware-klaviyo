<?php

namespace Klaviyo\Integration\Klaviyo\Gateway;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetLists\DTO\ProfilesListInfo;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetLists\GetProfilesListsRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetLists\GetProfilesListsResponse;
use Klaviyo\Integration\Klaviyo\Gateway\Exception\ProfilesListNotFoundException;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class ListIdByListName
{
    private ClientRegistry $clientRegistry;

    public function __construct(ClientRegistry $clientRegistry)
    {
        $this->clientRegistry = $clientRegistry;
    }

    public function execute(
        SalesChannelEntity $salesChannelEntity,
        string $listName
    ): string {
        $request = new GetProfilesListsRequest();
        $clientResult = $this->clientRegistry
            ->getClient($salesChannelEntity->getId())
            ->sendRequests([$request]);

        /** @var GetProfilesListsResponse $result */
        $result = $clientResult->getRequestResponse($request);
        if ( ! $result->isSuccess()) {
            throw new ProfilesListNotFoundException(
                sprintf('Could not get Profiles list from Klaviyo. Reason: %s', $result->getErrorDetails())
            );
        }

        /** @var ProfilesListInfo $list */
        foreach ($result->getLists() as $list) {
            if ($list->getName() === $listName) {
                return $list->getId();
            }
        }

        throw new ProfilesListNotFoundException(
            sprintf('Profiles list[name: "%s"] was not found', $listName)
        );
    }

}