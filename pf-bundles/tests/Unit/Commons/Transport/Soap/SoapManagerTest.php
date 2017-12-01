<?php declare(strict_types=1);

namespace Tests\Unit\Commons\Transport\Soap;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Soap\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\Soap\Dto\ResponseHeaderDto;
use Hanaboso\PipesFramework\Commons\Transport\Soap\Dto\Wsdl\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Soap\SoapClientFactory;
use Hanaboso\PipesFramework\Commons\Transport\Soap\SoapManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SoapClient;

/**
 * Class SoapManagerTest
 *
 * @package Tests\Unit\Commons\Transport\Soap
 */
final class SoapManagerTest extends TestCase
{

    /**
     * @covers SoapManager::send()
     */
    public function testSend(): void
    {
        $soapCallResponse    = 'abc';
        $lastResponseHeaders = 'def';

        $client = $this->createPartialMock(SoapClient::class, ['__soapCall', '__getLastResponseHeaders']);
        $client->method('__soapCall')->willReturn($soapCallResponse);
        $client->method('__getLastResponseHeaders')->willReturn($lastResponseHeaders);

        /** @var MockObject|SoapClientFactory $soapClientFactory */
        $soapClientFactory = $this->createPartialMock(SoapClientFactory::class, ['create']);
        $soapClientFactory->method('create')->willReturn($client);

        $request = new RequestDto('', [], '', new Uri(''));
        $request->setVersion(SOAP_1_2);

        $soapManager = new SoapManager($soapClientFactory);
        $result      = $soapManager->send($request);

        $this->assertInstanceOf(ResponseDto::class, $result);
        $this->assertEquals($soapCallResponse, $result->getSoapCallResponse());
        $this->assertEquals($lastResponseHeaders, $result->getLastResponseHeaders());
        $this->assertInstanceOf(ResponseHeaderDto::class, $result->getResponseHeaderDto());
    }

}