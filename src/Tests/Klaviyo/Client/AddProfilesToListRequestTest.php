<?php

namespace Klaviyo\Integration\Tests\Klaviyo\Client;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\AddMembersToList\AddProfilesToListRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\AddMembersToList\AddProfilesToListResponse;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common\ProfileContactInfo;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common\ProfileContactInfoCollection;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common\ProfileInfo;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common\ProfileInfoCollection;
use Klaviyo\Integration\Klaviyo\Client\Client;
use Klaviyo\Integration\Klaviyo\Client\Exception\TranslationException;
use Klaviyo\Integration\Tests\AbstractTestCase;
use Klaviyo\Integration\Tests\DataFixtures\RegisterDefaultSalesChannel;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class AddProfilesToListRequestTest extends AbstractTestCase
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
        $listId = 'G23fghj';
        $profiles = new ProfileContactInfoCollection(
            [new ProfileContactInfo('foo@example.com'), new ProfileContactInfo('bar@example.com')]
        );
        $request = new AddProfilesToListRequest($listId, $profiles);

        $apiResponseBody = '[{"id":"fooId","email":"foo@example.com"},{"id":"barId","email":"bar@example.com"}]';
        $apiResponse = new Response(200, ['Content-Type' => ['application/json']], $apiResponseBody);

        $expectedUrl = sprintf(
            'https://a.klaviyo.com/api/v2/list/%s/members?api_key=%s',
            $listId,
            self::PRIVATE_API_KEY_STUB
        );

        $expectedBody = '{"profiles":[{"email":"foo@example.com"},{"email":"bar@example.com"}]}';
        $expectedHeaders = [
            'Host' => ['a.klaviyo.com'],
            'Accept' => ['application/json'],
            'Content-Type' => ['application/json']
        ];

        $this->guzzleClient->expects($this->once())
            ->method('send')
            ->with(
                $this->callback(function (Request $request) use ($expectedUrl, $expectedBody, $expectedHeaders) {
                    self::assertEquals($request->getMethod(), 'POST', 'Expected request method does not match actual');

                    $actualUrl = (string)$request->getUri();
                    self::assertEquals($expectedUrl, $actualUrl, 'Expected request url does not match actual');

                    $actualBody = $request->getBody()->getContents();
                    self::assertEquals($expectedBody, $actualBody, 'Expected request body does not match actual');

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
            )->willReturn($apiResponse);

        $apiResponse = $this->client->sendRequest($request);

        $profiles = new ProfileInfoCollection(
            [
                new ProfileInfo('fooId', 'foo@example.com'),
                new ProfileInfo('barId', 'bar@example.com')
            ]
        );
        $expectedResponse = new AddProfilesToListResponse(true, $profiles);
        self::assertEquals($expectedResponse, $apiResponse);
    }

    public function testFailedRequestWithJsonResponseWhereMessageDefinedInDetailField()
    {
        $listId = 'G23fghj';
        $profiles = new ProfileContactInfoCollection(
            [new ProfileContactInfo('foo@example.com'), new ProfileContactInfo('bar@example.com')]
        );
        $request = new AddProfilesToListRequest($listId, $profiles);

        $apiResponseBody = '{"detail":"profiles is a required parameter."}';
        $apiResponse = new Response(400, ['Content-Type' => ['application/json']], $apiResponseBody);

        $this->guzzleClient->expects($this->once())
            ->method('send')
            ->willReturn($apiResponse);

        $apiResponse = $this->client->sendRequest($request);

        $expectedResponse = new AddProfilesToListResponse(
            false,
            new ProfileInfoCollection(),
            'profiles is a required parameter.'
        );
        self::assertEquals($expectedResponse, $apiResponse);
    }

    public function testFailedRequestWithJsonResponseWhereMessageDefinedInMessageField()
    {
        $listId = 'G23fghj';
        $profiles = new ProfileContactInfoCollection(
            [new ProfileContactInfo('foo@example.com'), new ProfileContactInfo('bar@example.com')]
        );
        $request = new AddProfilesToListRequest($listId, $profiles);

        $apiResponseBody = '{"message":"Api key is invalid"}';
        $apiResponse = new Response(400, ['Content-Type' => ['application/json']], $apiResponseBody);

        $this->guzzleClient->expects($this->once())
            ->method('send')
            ->willReturn($apiResponse);

        $apiResponse = $this->client->sendRequest($request);

        $expectedResponse = new AddProfilesToListResponse(
            false,
            new ProfileInfoCollection(),
            'Api key is invalid'
        );
        self::assertEquals($expectedResponse, $apiResponse);
    }

    public function testFailedRequestWithNotJsonResponse()
    {
        $listId = 'G23fghj';
        $profiles = new ProfileContactInfoCollection(
            [new ProfileContactInfo('foo@example.com'), new ProfileContactInfo('bar@example.com')]
        );
        $request = new AddProfilesToListRequest($listId, $profiles);

        $apiResponse = new Response(404, [], 'Not found');

        $this->guzzleClient->expects($this->once())
            ->method('send')
            ->willReturn($apiResponse);

        $this->expectException(TranslationException::class);
        $this->expectExceptionMessage(
            'Failed to translate "GuzzleHttp\Psr7\Response". Reason: Invalid response status code 404'
        );
        $this->client->sendRequest($request);
    }

    public function testSuccessRequestWithNotJsonResponse()
    {
        $listId = 'G23fghj';
        $profiles = new ProfileContactInfoCollection(
            [new ProfileContactInfo('foo@example.com'), new ProfileContactInfo('bar@example.com')]
        );
        $request = new AddProfilesToListRequest($listId, $profiles);

        $apiResponse = new Response(200, [], 'Some content');

        $this->guzzleClient->expects($this->once())
            ->method('send')
            ->willReturn($apiResponse);

        $this->expectException(TranslationException::class);
        $this->expectExceptionMessage(
            'Failed to translate "GuzzleHttp\Psr7\Response". ' .
            'Reason: Add Profiles to list api response expected to be a JSON'
        );
        $this->client->sendRequest($request);
    }
}