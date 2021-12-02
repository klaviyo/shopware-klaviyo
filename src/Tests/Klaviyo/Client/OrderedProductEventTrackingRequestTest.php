<?php

namespace Klaviyo\Integration\Tests\Klaviyo\Client;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\Common\CustomerProperties;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\Common\EventTrackingResponse;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderedProductEvent\OrderedProductEventTrackingRequest;
use Klaviyo\Integration\Klaviyo\Client\Client;
use Klaviyo\Integration\Tests\AbstractTestCase;
use Klaviyo\Integration\Tests\DataFixtures\RegisterDefaultSalesChannel;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class OrderedProductEventTrackingRequestTest extends AbstractTestCase
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
        $time = new \DateTime('@1387312956', new \DateTimeZone('UTC'));

        $request = new OrderedProductEventTrackingRequest(
            'some event id',
            $time,
            new CustomerProperties(
                'foo@example.com',
                'John',
                'Doe',
                '+134234324324',
                '3287 College Avenue',
                'Dayton',
                '45402',
                'Ohio',
                'USA',
            ),
            22.3456,
            'Foo order id',
            'Foo Product ID',
            'sku12345',
            'Foo product',
            42,
            'https://example.com/product/Some%20Product%20ID',
            'https://example.com/product/Some%20Product%20ID/image/222',
            [
                'foo category id',
                'bar category id',
            ],
            'Nike'
        );

        $expectedUrl = 'https://a.klaviyo.com/api/track';

        /*
         * Expected request body(in actual will be without line endings)
         *
         * {"token":"private_api_key_stub","event":"Ordered Product","customer_properties":{"$email":"foo@example.com",
         * "$first_name":"John","$last_name":"Doe","$address1":"3287 College Avenue","$phone_number":"+134234324324",
         * "$city":"Dayton","$region":"Ohio","$country":"USA","$zip":"45402"},"properties":
         * {"ProductName":"Foo product","$value":22.3456,"$event_id":"Foo Product ID","OrderId":"Foo order id",
         * "ProductID":"Foo Product ID","SKU":"sku12345","Quantity":42,"ProductURL":
         * "https:\/\/example.com\/product\/Some%20Product%20ID",
         * "ImageURL":"https:\/\/example.com\/product\/Some%20Product%20ID\/image\/222",
         * "Categories":["foo category id","bar category id"],"ProductBrand":"Nike"},"time":1387312956}
         */
        $expectedBody = 'data=%7B%22token%22%3A%22private_api_key_stub%22%2C%22event%22%3A%22Ordered+Product%22%2C%22' .
            'customer_properties%22%3A%7B%22%24email%22%3A%22foo%40example.com%22%2C%22%24first_name%22%3A%22John' .
            '%22%2C%22%24last_name%22%3A%22Doe%22%2C%22%24address1%22%3A%223287+College+Avenue%22%2C%22%24' .
            'phone_number%22%3A%22%2B134234324324%22%2C%22%24city%22%3A%22Dayton%22%2C%22%24region' .
            '%22%3A%22Ohio%22%2C%22%24country%22%3A%22USA%22%2C%22%24zip%22%3A%2245402%22%7D%2C%' .
            '22properties%22%3A%7B%22ProductName%22%3A%22Foo+product%22%2C%22%24value%22%3A22.3456' .
            '%2C%22%24event_id%22%3A%22Foo+Product+ID%22%2C%22OrderId%22%3A%22Foo+order+id' .
            '%22%2C%22ProductID%22%3A%22Foo+Product+ID%22%2C%22SKU%22%3A%22sku12345%22%2C' .
            '%22Quantity%22%3A42%2C%22ProductURL%22%3A%22https%3A%5C%2F%5C%2Fexample.com' .
            '%5C%2Fproduct%5C%2FSome%2520Product%2520ID%22%2C%22ImageURL%22%3A%22https' .
            '%3A%5C%2F%5C%2Fexample.com%5C%2Fproduct%5C%2FSome%2520Product%2520ID%5C' .
            '%2Fimage%5C%2F222%22%2C%22Categories%22%3A%5B%22foo+category+id%22%2C%22bar+category+id' .
            '%22%5D%2C%22ProductBrand%22%3A%22Nike%22%7D%2C%22time%22%3A1387312956%7D';

        $expectedHeaders = [
            'Host' => ['a.klaviyo.com'],
            'Accept' => ['text/html'],
            'Content-Type' => ['application/x-www-form-urlencoded']
        ];

        $response = new Response(200, [], '1');
        $this->guzzleClient->expects($this->once())
            ->method('send')
            ->with(
                $this->callback(function(Request $request) use ($expectedUrl, $expectedBody, $expectedHeaders) {
                    self::assertEquals($request->getMethod(), 'POST', 'Expected request method does not match actual');

                    $actualUrl = (string)$request->getUri();
                    self::assertEquals($expectedUrl, $actualUrl, 'Expected request url does not match actual');

                    $actualBody = $request->getBody()->getContents();
                    self::assertEquals($expectedBody, $actualBody, 'Expected request body does not match actual');

                    $actualHeaders = $request->getHeaders();
                    self::assertEquals($expectedHeaders, $actualHeaders, 'Expected request headers does not match actual');

                    return true;
                }),
                [
                    RequestOptions::CONNECT_TIMEOUT => 15,
                    RequestOptions::TIMEOUT => 30,
                    RequestOptions::HTTP_ERRORS => false,
                ]
            )->willReturn($response);

        $actualResponseDTO = $this->client->sendRequest($request);
        $expectedResponse = new EventTrackingResponse(true);
        self::assertEquals($expectedResponse, $actualResponseDTO);
    }

    public function testFailedRequest()
    {
        $time = new \DateTime('@1387312956', new \DateTimeZone('UTC'));

        $request = new OrderedProductEventTrackingRequest(
            'some event id',
            $time,
            new CustomerProperties(
                'foo@example.com',
                'John',
                'Doe',
                '+134234324324',
                '3287 College Avenue',
                'Dayton',
                '45402',
                'Ohio',
                'USA',
            ),
            22.3456,
            'Foo order id',
            'Foo Product ID',
            'sku12345',
            'Foo product',
            42,
            'https://example.com/product/Some%20Product%20ID',
            'https://example.com/product/Some%20Product%20ID/image/222',
            [
                'foo category id',
                'bar category id',
            ],
            'Nike'
        );

        $response = new Response(200, [], '');
        $this->guzzleClient->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $actualResponseDTO = $this->client->sendRequest($request);
        $expectedResponse = new EventTrackingResponse(false);
        self::assertEquals($expectedResponse, $actualResponseDTO);
    }
}