<?php

namespace Klaviyo\Integration\Tests\Klaviyo\Client;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common\ProfileInfo;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common\ProfileInfoCollection;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetListProfiles\GetListProfilesRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetListProfiles\GetListProfilesResponse;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetLists\DTO\ProfilesListInfo;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetLists\DTO\ProfilesListInfoCollection;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetLists\GetProfilesListsRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetLists\GetProfilesListsResponse;
use Klaviyo\Integration\Klaviyo\Client\Client;
use Klaviyo\Integration\Klaviyo\Client\Exception\TranslationException;
use Klaviyo\Integration\Tests\AbstractTestCase;
use Klaviyo\Integration\Tests\DataFixtures\RegisterDefaultSalesChannel;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class GetListProfilesRequestTest extends AbstractTestCase
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

    public function testSuccessRequestWithMarker()
    {
        $listId = 'G23fghj';
        $cursorMarker = 14334234;

        $request = new GetListProfilesRequest($listId, $cursorMarker);

        $responseBody = <<<JSON
    {
        "records": [
            {
                "id":"fooId",
                "email":"foo@example.com"
            },
            {
                "id":"barId",
                "email":"bar@example.com"
            }
        ],
        "marker": 567899
    }
JSON;

        $response = new Response(200, ['Content-Type' => ['application/json']], $responseBody);

        $expectedUrl = sprintf(
            'https://a.klaviyo.com/api/v2/group/%s/members/all?api_key=%s&marker=%s',
            $listId,
            self::PRIVATE_API_KEY_STUB,
            $cursorMarker
        );
        $expectedHeaders = [
            'Host' => ['a.klaviyo.com'],
            'Accept' => ['application/json'],
        ];

        $this->guzzleClient->expects($this->once())
            ->method('send')
            ->with(
                $this->callback(function (Request $request) use ($expectedUrl, $expectedHeaders) {
                    self::assertEquals($request->getMethod(), 'GET', 'Expected request method does not match actual');

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

        $expectedResponse = new GetListProfilesResponse(
            true,
            new ProfileInfoCollection(
                [
                    new ProfileInfo('fooId', 'foo@example.com'),
                    new ProfileInfo('barId', 'bar@example.com')
                ]
            ),
            567899
        );
        self::assertEquals($expectedResponse, $response);
    }

    public function testSuccessRequestWithoutMarker()
    {
        $listId = 'G23fghj';

        $request = new GetListProfilesRequest($listId, null);

        $responseBody = <<<JSON
    {
        "records": [
            {
                "id":"fooId",
                "email":"foo@example.com"
            },
            {
                "id":"barId",
                "email":"bar@example.com"
            }
        ]
    }
JSON;

        $response = new Response(200, ['Content-Type' => ['application/json']], $responseBody);

        $expectedUrl = sprintf(
            'https://a.klaviyo.com/api/v2/group/%s/members/all?api_key=%s',
            $listId,
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
                    self::assertEquals($request->getMethod(), 'GET', 'Expected request method does not match actual');

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

        $expectedResponse = new GetListProfilesResponse(
            true,
            new ProfileInfoCollection(
                [
                    new ProfileInfo('fooId', 'foo@example.com'),
                    new ProfileInfo('barId', 'bar@example.com')
                ]
            )
        );
        self::assertEquals($expectedResponse, $response);
    }

    public function testFailedRequestWithDetailsInDetailField()
    {
        $listId = 'G23fghj';

        $request = new GetListProfilesRequest($listId, null);

        $responseBody = <<<JSON
    {"detail": "List G23fghj does not exist or is inactive."}
JSON;

        $response = new Response(404, ['Content-Type' => ['application/json']], $responseBody);

        $this->guzzleClient->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $response = $this->client->sendRequest($request);

        $expectedResponse = new GetListProfilesResponse(
            false,
            new ProfileInfoCollection(),
            null,
            'List G23fghj does not exist or is inactive.'
        );
        self::assertEquals($expectedResponse, $response);
    }

    public function testFailedRequestWithDetailsInMessageField()
    {
        $listId = 'G23fghj';

        $request = new GetListProfilesRequest($listId, null);

        $responseBody = <<<JSON
    {"message": "Invalid api key"}
JSON;

        $response = new Response(400, ['Content-Type' => ['application/json']], $responseBody);

        $this->guzzleClient->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $response = $this->client->sendRequest($request);

        $expectedResponse = new GetListProfilesResponse(
            false,
            new ProfileInfoCollection(),
            null,
            'Invalid api key'
        );
        self::assertEquals($expectedResponse, $response);
    }

    public function testFailedRequestWithNotJsonResponse()
    {
        $listId = 'G23fghj';

        $request = new GetListProfilesRequest($listId, null);

        $response = new Response(400, [], 'Some content');

        $this->guzzleClient->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $this->expectException(TranslationException::class);
        $this->expectExceptionMessage(
            'Failed to translate "GuzzleHttp\Psr7\Response". Reason: Invalid response status code 400'
        );

        $this->client->sendRequest($request);
    }

    public function testFailedRequestWith200ResponseButNotJson()
    {
        $listId = 'G23fghj';

        $request = new GetListProfilesRequest($listId, null);

        $response = new Response(200, [], 'Some content');

        $this->guzzleClient->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $this->expectException(TranslationException::class);
        $this->expectExceptionMessage(
            'Failed to translate "GuzzleHttp\Psr7\Response". ' .
            'Reason: Get list profiles api response expected to be a JSON'
        );

        $this->client->sendRequest($request);
    }
}