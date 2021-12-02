<?php

namespace Klaviyo\Integration\Tests\Klaviyo\Client;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common\ProfileContactInfo;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common\ProfileContactInfoCollection;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\RemoveProfilesFromList\RemoveProfilesFromListRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\RemoveProfilesFromList\RemoveProfilesFromListResponse;
use Klaviyo\Integration\Klaviyo\Client\Client;
use Klaviyo\Integration\Klaviyo\Client\Exception\TranslationException;
use Klaviyo\Integration\Tests\AbstractTestCase;
use Klaviyo\Integration\Tests\DataFixtures\RegisterDefaultSalesChannel;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class RemoveProfilesFromListRequestTest extends AbstractTestCase
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
        $request = new RemoveProfilesFromListRequest($listId, $profiles);

        $response = new Response();

        $expectedUrl = sprintf(
            'https://a.klaviyo.com/api/v2/list/%s/members?api_key=%s',
            $listId,
            self::PRIVATE_API_KEY_STUB
        );

        $expectedBody = '{"emails":["foo@example.com","bar@example.com"]}';
        $expectedHeaders = [
            'Host' => ['a.klaviyo.com'],
            'Content-Type' => ['application/json']
        ];

        $this->guzzleClient->expects($this->once())
            ->method('send')
            ->with(
                $this->callback(function (Request $request) use ($expectedUrl, $expectedBody, $expectedHeaders) {
                    self::assertEquals($request->getMethod(), 'DELETE', 'Expected request method does not match actual');

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
            )->willReturn($response);

        $response = $this->client->sendRequest($request);
        $expectedResponse = new RemoveProfilesFromListResponse(true);
        self::assertEquals($expectedResponse, $response);
    }

    public function testFailedRequestWithJsonResponse()
    {
        $listId = 'G23fghj';
        $profiles = new ProfileContactInfoCollection(
            [new ProfileContactInfo('foo@example.com'), new ProfileContactInfo('bar@example.com')]
        );
        $request = new RemoveProfilesFromListRequest($listId, $profiles);

        $errorBody = '{"detail":"Something bad happened"}';
        $response = new Response(400, ['Content-Type' => ['application/json']], $errorBody);

        $this->guzzleClient->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $response = $this->client->sendRequest($request);
        $expectedResponse = new RemoveProfilesFromListResponse(false, 'Something bad happened');
        self::assertEquals($expectedResponse, $response);
    }

    public function testFailedRequestWithNotJsonResponse()
    {
        $listId = 'G23fghj';
        $profiles = new ProfileContactInfoCollection(
            [new ProfileContactInfo('foo@example.com'), new ProfileContactInfo('bar@example.com')]
        );
        $request = new RemoveProfilesFromListRequest($listId, $profiles);

        // Such exception could happen in case if list id is incorrect
        $response = new Response(404, []);

        $this->guzzleClient->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $this->expectException(TranslationException::class);
        $this->expectExceptionMessage(
            'Failed to translate "GuzzleHttp\Psr7\Response". Reason: Invalid response status code 404'
        );

        $this->client->sendRequest($request);
    }
}