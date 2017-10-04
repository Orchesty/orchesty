<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/4/17
 * Time: 2:10 PM
 */

namespace Tests\Curl;

use CcApi\Curl\ClientFactory;
use CcApi\Curl\CurlSender;
use CcApi\Curl\Exception\CurlException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Http\Message\ResponseInterface;

/**
 * Class CurlSenderTest
 *
 * @package Tests\Curl
 */
class CurlSenderTest extends TestCase
{

    /**
     * @covers CurlSender::send()
     */
    public function testSend(): void
    {
        /** @var Client|PHPUnit_Framework_MockObject_MockObject $client */
        $client = $this->createMock(Client::class);
        $client->method('send')->willReturn(new Response(200));

        /** @var ClientFactory|PHPUnit_Framework_MockObject_MockObject $clientFactory */
        $clientFactory = $this->createMock(ClientFactory::class);
        $clientFactory->method('create')->willReturn($client);

        $curlSender = new CurlSender($clientFactory);

        $response = $curlSender->send(new Request(CurlSender::GET, new Uri('http://example.com')));

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    /**
     * @covers CurlSender::send()
     */
    public function testSendException(): void
    {
        $request = new Request(CurlSender::GET, new Uri('http://example.com'));

        /** @var Client|PHPUnit_Framework_MockObject_MockObject $client */
        $client = $this->createMock(Client::class);
        $client->method('send')->willThrowException(new ClientException('Bad request', $request));

        /** @var ClientFactory|PHPUnit_Framework_MockObject_MockObject $clientFactory */
        $clientFactory = $this->createMock(ClientFactory::class);
        $clientFactory->method('create')->willReturn($client);

        $curlSender = new CurlSender($clientFactory);

        $this->expectException(CurlException::class);
        $this->expectExceptionMessage('Curl sender error: Bad request');
        $curlSender->send($request);
    }

}