<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Transport\Curl;

use CleverConnectors\AppBundle\Transport\Curl\CMCurlManager;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlClientFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlException;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use ReflectionClass;

/**
 * Class CMCurlManagerTest
 *
 * @package Tests\Unit\AppBundle\Transport\Curl
 */
final class CMCurlManagerTest extends TestCase
{

    /**
     * @covers CMCurlManager::send()
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

        $requestDto = new RequestDto(CMCurlManager::METHOD_GET, new Uri('http://example.com'));

        $curlManager = new CMCurlManager($curlClientFactory, ['cert' => '', 'ca' => '']);
        $result      = $curlManager->send($requestDto);

        $this->assertInstanceOf(ResponseDto::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('OK', $result->getReasonPhrase());
        $this->assertEquals(['header_key' => ['header_value']], $result->getHeaders());
        $this->assertEquals($body, $result->getBody());
    }

    /**
     * @covers CMCurlManager::send()
     */
    public function testSendFail(): void
    {
        $this->expectException(CurlException::class);
        $requestDto  = new RequestDto(CMCurlManager::METHOD_GET, new Uri('http://example.com'));
        $curlManager = new CMCurlManager(new CurlClientFactory(), []);
        $curlManager->send($requestDto, ['headers' => 123]);
    }

    /**
     * @covers CMCurlManager::send()
     */
    public function testSendFailMethod(): void
    {
        $this->expectException(CurlException::class);
        $this->expectExceptionCode(CurlException::INVALID_METHOD);
        new RequestDto('nonsense', new Uri('http://example.com'));
    }

    /**
     * @covers CMCurlManager::send()
     */
    public function testSendFailBody(): void
    {
        $this->expectException(CurlException::class);
        $this->expectExceptionCode(CurlException::BODY_ON_GET);
        $requestDto = new RequestDto(CMCurlManager::METHOD_GET, new Uri('http://example.com'));
        $requestDto->setBody('');
    }

    /**
     * @covers CMCurlManager::prepareOptions()
     */
    public function testPrepareOptions(): void
    {
        $secret = [
            'cert' => 'abc',
            'ca'   => 'def',
        ];

        $anotherParam = ['another' => 'param'];

        $reflection = new ReflectionClass(CMCurlManager::class);
        $method     = $reflection->getMethod('prepareOptions');
        $method->setAccessible(TRUE);

        $curlManager = new CMCurlManager(new CurlClientFactory(), $secret);
        $res         = $method->invokeArgs($curlManager, ['options' => $anotherParam]);

        $this->assertEquals(
            [
                'cert'    => 'abc',
                'ssl_key' => 'abc',
                'verify'  => 'def',
                'another' => 'param',
            ],
            $res
        );
    }

}