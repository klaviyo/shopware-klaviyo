<?php declare(strict_types=1);

namespace Klaviyo\Integration\Test;

use GuzzleHttp\Client;
use Klaviyo\Integration\Klaviyo\Gateway\Exception\ProfilesListNotFoundException;
use Klaviyo\Integration\Klaviyo\Gateway\KlaviyoGateway;
use Klaviyo\Integration\Subscriber\Job\FullResynchronizationJobProcessor;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class KlaviyoSubscriberManagement
{
    private SalesChannelEntity $salesChannelEntity;
    private KlaviyoGateway $klaviyoGateway;
    private Client $guzzleClient;

    public function __construct(
        SalesChannelEntity $salesChannelEntity,
        KlaviyoGateway $klaviyoGateway,
        ?Client $guzzleClient = null
    ) {
        $this->salesChannelEntity = $salesChannelEntity;
        $this->klaviyoGateway = $klaviyoGateway;
        $this->guzzleClient = $guzzleClient ?? new Client();
    }

    public function getProfileMetrics(string $profileId): array
    {
        $response = $this->guzzleClient->request(
            'GET',
            sprintf(
                'https://a.klaviyo.com/api/v1/person/%s/metrics/timeline?count=50&sort=desc&api_key=%s',
                $profileId,
                KLAVIYO_PRIVATE_KEY
            ),
            [
                \GuzzleHttp\RequestOptions::HEADERS => [
                    'Accept' => 'application/json',
                ]
            ]
        );

        return \json_decode($response->getBody()->getContents(), true);
    }

    public function addEmailsToList($listId, array $emails): array
    {
        $emailsBatches = array_chunk($emails, FullResynchronizationJobProcessor::DEFAULT_SUBSCRIBERS_EXPORT_CHUNK_SIZE);
        $personsData = [];

        foreach ($emailsBatches as $emailBatch) {
            $payload['profiles'] = array_map(function ($email) {
                return ['email' => $email];
            }, $emailBatch);

            $response = $this->guzzleClient->request(
                'POST',
                sprintf('https://a.klaviyo.com/api/v2/list/%s/members?api_key=%s', $listId, KLAVIYO_PRIVATE_KEY),
                [
                    \GuzzleHttp\RequestOptions::HEADERS => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json'
                    ],
                    \GuzzleHttp\RequestOptions::BODY => \json_encode($payload)
                ]
            );

            $personsData = array_merge($personsData, (array)\json_decode($response->getBody()->getContents(), true));
        }

        return $personsData;
    }

    public function getKlaviyoListMembers($listId, int $marker = 0): array
    {
        $urlSchema = $marker === 0
            ? 'https://a.klaviyo.com/api/v2/group/%s/members/all?api_key=%s'
            : 'https://a.klaviyo.com/api/v2/group/%s/members/all?api_key=%s&marker=%s';
        $response = $this->guzzleClient->get(
            sprintf($urlSchema, $listId, KLAVIYO_PRIVATE_KEY, $marker),
        );

        $responseData = \json_decode($response->getBody()->getContents(), true);
        $marker = $responseData['marker'] ?? 0;
        $records = $responseData['records'] ?: [];

        if ($marker) {
            $records = array_merge($records, $this->getKlaviyoListMembers($listId, $marker));
        }

        return $records;
    }

    public function deleteKlaviyoTestList(string $listName): void
    {
        try {
            $listId = $this->klaviyoGateway->getListIdByListName($this->salesChannelEntity, $listName);
            $request = new \GuzzleHttp\Psr7\Request(
                'DELETE',
                sprintf('https://a.klaviyo.com/api/v2/list/%s?api_key=%s', $listId, KLAVIYO_PRIVATE_KEY)
            );

            $this->guzzleClient->sendRequest($request);
        } catch (ProfilesListNotFoundException $e) {
            null;
        }
    }

    public function createKlaviyoTestList(string $listName): string
    {
        $requestBodyObject = new \stdClass();
        $requestBodyObject->list_name = $listName;

        $response = $this->guzzleClient->request(
            'POST',
            sprintf('https://a.klaviyo.com/api/v2/lists?api_key=%s', KLAVIYO_PRIVATE_KEY),
            [
                \GuzzleHttp\RequestOptions::HEADERS => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ],
                \GuzzleHttp\RequestOptions::FORM_PARAMS => [
                    'list_name' => $listName
                ]
            ]
        );
        $responseData = \json_decode($response->getBody()->getContents(), true);

        return (string)$responseData['list_id'];
    }
}
