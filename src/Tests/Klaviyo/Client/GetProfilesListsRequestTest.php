<?php

namespace Klaviyo\Integration\Tests\Klaviyo\Client;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetLists\DTO\ProfilesListInfo;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetLists\DTO\ProfilesListInfoCollection;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetLists\GetProfilesListsRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetLists\GetProfilesListsResponse;
use Klaviyo\Integration\Klaviyo\Client\Client;
use Klaviyo\Integration\Tests\AbstractTestCase;
use Klaviyo\Integration\Tests\DataFixtures\RegisterDefaultSalesChannel;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class GetProfilesListsRequestTest extends AbstractTestCase
{
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->executeFixtures([new RegisterDefaultSalesChannel()]);

        /** @var SalesChannelEntity $salesChannelEntity */
        $salesChannelEntity = $this->getByReference('klaviyo_tracking_integration.sales_channel.storefront');

        $registry = $this->getContainer()->get('klaviyo.tracking_integration.gateway.client_registry.test');
        $this->client = $registry->getClient($salesChannelEntity);
    }

    public function testSuccessRequest()
    {
        $request = new GetProfilesListsRequest();

        $responseBody = <<<JSON
    [
        {
            "list_id":"LIST_ID_1",
            "list_name":"MyFirstList"
        },
        {
            "list_id":"LIST_ID_2",
            "list_name":"MySecondList"
        }
    ]
JSON;

        $response = new Response(200, ['Content-Type' => ['application/json']], $responseBody);

        $expectedUrl = sprintf(
            'https://a.klaviyo.com/api/v2/lists?api_key=%s',
            self::PRIVATE_API_KEY_STUB
        );
        $expectedHeaders = [
            'Host' => ['a.klaviyo.com'],
            'Accept' => ['application/json'],
        ];

        $this->guzzleClient->expects($this->once())
            ->method('send')
            ->with(
                $this->callback(function (Request $request) use ($expectedUrl, $expectedHeaders) {
                    $actualUrl = (string)$request->getUri();
                    self::assertEquals($expectedUrl, $actualUrl, 'Expected request url does not match actual');

                    $actualHeaders = $request->getHeaders();
                    self::assertEquals(
                        $expectedHeaders,
                        $actualHeaders,
                        'Expected request headers does not match actual'
                    );

                    return true;
                }),
                [
                    RequestOptions::CONNECT_TIMEOUT => 15,
                    RequestOptions::TIMEOUT => 30,
                    RequestOptions::HTTP_ERRORS => false,
                ]
            )->willReturn($response);

        $response = $this->client->sendRequest($request);

        $lists = new ProfilesListInfoCollection(
            [
                new ProfilesListInfo('LIST_ID_1', 'MyFirstList'),
                new ProfilesListInfo('LIST_ID_2', 'MySecondList')
            ]
        );
        $expectedResponse = new GetProfilesListsResponse(true, $lists);
        self::assertEquals($expectedResponse, $response);
    }

    public function testFailedRequestWithJsonResponseWhereResponseErrorKeyIsDetail()
    {
        $request = new GetProfilesListsRequest();

        // Key will be detail if something wrong with request
        $responseBody = <<<JSON
    {
        "detail":"Could not satisfy the request Accept header."
    }
JSON;

        $response = new Response(406, ['Content-Type' => ['application/json']], $responseBody);

        $this->guzzleClient->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $response = $this->client->sendRequest($request);

        $expectedResponse = new GetProfilesListsResponse(
            false,
            new ProfilesListInfoCollection(),
            'Could not satisfy the request Accept header.'
        );
        self::assertEquals($expectedResponse, $response);
    }

    public function testFailedRequestWithJsonResponseWhereResponseErrorKeyIsMessage()
    {
        $request = new GetProfilesListsRequest();

        // Key will be detail if something wrong with api key
        $responseBody = <<<JSON
    {
        "message":"Api key is incorrect."
    }
JSON;

        $response = new Response(400, ['Content-Type' => ['application/json']], $responseBody);

        $this->guzzleClient->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $response = $this->client->sendRequest($request);

        $expectedResponse = new GetProfilesListsResponse(
            false,
            new ProfilesListInfoCollection(),
            'Api key is incorrect.'
        );
        self::assertEquals($expectedResponse, $response);
    }
}