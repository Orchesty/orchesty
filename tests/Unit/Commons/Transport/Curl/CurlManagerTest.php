<?php declare(strict_types=1);

namespace Tests\Unit\Commons\Transport\Curl;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlClientFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlException;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;

/**
 * Class CurlManagerTest
 *
 * @package Tests\Unit\Commons\Transport\Curl
 */
final class CurlManagerTest extends TestCase
{

    /**
     * @covers CurlManager::send()
     */
    public function testSend(): void
    {
        $body    = json_encode(['abc' => 'def']);
        $headers = ['header_key' => 'header_value'];

        $psr7Response = new Response(200, $headers, $body);

        /** @var PHPUnit_Framework_MockObject_MockObject|Client $client */
        $client = $this->createPartialMock(Client::class, ['send']);
        $client->method('send')->willReturn($psr7Response);

        /** @var PHPUnit_Framework_MockObject_MockObject|CurlClientFactory $curlClientFactory */
        $curlClientFactory = $this->createPartialMock(CurlClientFactory::class, ['create']);
        $curlClientFactory->method('create')->willReturn($client);

        $requestDto = new RequestDto(CurlManager::METHOD_GET, new Uri('http://example.com'));

        $curlManager = new CurlManager($curlClientFactory);
        $result      = $curlManager->send($requestDto);

        $this->assertInstanceOf(ResponseDto::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('OK', $result->getReasonPhrase());
        $this->assertEquals(['header_key' => ['header_value']], $result->getHeaders());
        $this->assertEquals($body, $result->getBody());
    }

    /**
     * @covers CurlManager::send()
     */
    public function testSendFail(): void
    {
        $this->expectException(CurlException::class);
        $requestDto  = new RequestDto(CurlManager::METHOD_GET, new Uri('http://example.com'));
        $curlManager = new CurlManager(new CurlClientFactory());
        $curlManager->send($requestDto, ['headers' => 123]);
    }

    /**
     * @covers CurlManager::send()
     */
    public function testSendFailMethod(): void
    {
        $this->expectException(CurlException::class);
        $this->expectExceptionCode(CurlException::INVALID_METHOD);
        new RequestDto('nonsense', new Uri('http://example.com'));
    }

    /**
     * @covers CurlManager::send()
     */
    public function testSendFailBody(): void
    {
        $this->expectException(CurlException::class);
        $this->expectExceptionCode(CurlException::BODY_ON_GET);
        $requestDto = new RequestDto(CurlManager::METHOD_GET, new Uri('http://example.com'));
        $requestDto->setBody('');
    }

}