<?php declare(strict_types=1);

namespace Tests\Integration\Commons\Transport\Soap;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Soap\Dto\NonWsdl\RequestDto as RequestDtoNonWsdl;
use Hanaboso\PipesFramework\Commons\Transport\Soap\Dto\Wsdl\RequestDto as RequestDtoWsdl;
use Hanaboso\PipesFramework\Commons\Transport\Soap\SoapClient;
use Hanaboso\PipesFramework\Commons\Transport\Soap\SoapClientFactory;
use Hanaboso\PipesFramework\Commons\Transport\Soap\SoapException;
use PHPUnit\Framework\TestCase;

/**
 * Class SoapClientFactoryTest
 *
 * @package Tests\Unit\Commons\Transport\Soap
 */
final class SoapClientFactoryTest extends TestCase
{

    /**
     * @covers SoapClientFactory::create()
     */
    public function testCreateSoapClientWsdlFail(): void
    {
        $request = new RequestDtoWsdl('functionName', [], 'namespace', new Uri('abc'));
        $request->setVersion(SOAP_1_2);

        $this->expectException(SoapException::class);
        $this->expectExceptionCode(SoapException::INVALID_WSDL);

        $soapClientFactory = new SoapClientFactory();
        $soapClientFactory->create($request, ['uri' => '', 'location' => '']);
    }

    /**
     * @covers SoapClientFactory::create()
     */
    public function testCreateSoapClientNonWsdl(): void
    {
        $request = new RequestDtoNonWsdl('functionName', [], 'namespace', new Uri(''));
        $request->setVersion(SOAP_1_2);

        $soapClientFactory = new SoapClientFactory();
        $result            = $soapClientFactory->create($request, ['uri' => '', 'location' => '']);

        $this->assertInstanceOf(SoapClient::class, $result);
    }

}