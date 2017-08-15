<?php declare(strict_types=1);

namespace Tests\Unit\Commons\Transport\Soap;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Soap\Dto\NonWsdl\RequestDto as RequestDtoNonWsdl;
use Hanaboso\PipesFramework\Commons\Transport\Soap\Dto\Wsdl\RequestDto as RequestDtoWsdl;
use Hanaboso\PipesFramework\Commons\Transport\Soap\SoapHelper;
use PHPUnit\Framework\TestCase;
use SoapParam;
use SoapVar;

/**
 * Class SoapHelperTest
 *
 * @package Tests\Unit\Commons\Transport\Soap
 */
final class SoapHelperTest extends TestCase
{

    /**
     * @covers SoapHelper::composeRequestHeaders()
     */
    public function testComposeRequestHeaders()
    {
        $request = new RequestDtoNonWsdl('functionName', ['arguments'], 'namespace', new Uri(''));
        $result  = SoapHelper::composeRequestHeaders($request);

        $this->assertNull($result);
    }

    /**
     * @covers SoapHelper::composeArguments()
     */
    public function testComposeArgumentsWsdl()
    {
        $request = new RequestDtoWsdl('functionName', ['arguments'], 'namespace', new Uri(''));
        $result  = SoapHelper::composeArguments($request);

        $this->assertEquals($result, $request->getArguments());
    }

    /**
     * @covers SoapHelper::composeArguments()
     */
    public function testComposeArgumentsNonWsdl()
    {
        $request = new RequestDtoNonWsdl('functionName', ['key1' => 'value1'], 'namespace', new Uri(''));
        $result  = SoapHelper::composeArguments($request);

        $soapVar   = new SoapVar('value1', XSD_STRING, '', '', 'ns1:key1');
        $soapParam = new SoapParam($soapVar, 'key1');
        $this->assertEquals([$soapParam], $result);
    }

    /**
     * @covers SoapHelper::composeArguments()
     */
    public function testComposeArgumentsNonWsdlNull()
    {
        $request = new RequestDtoNonWsdl('functionName', [], 'namespace', new Uri(''));
        $result  = SoapHelper::composeArguments($request);

        $this->assertNull($result);
    }

    /**
     * @covers SoapHelper::parseResponseHeaders()
     */
    public function testParseResponseHeaders()
    {
        // TODO prepare string for testing
        // TODO register services at transport.yml
        // TODO fix Metrics tests

        $headers = '';
        $result  = SoapHelper::parseResponseHeaders($headers);

        $this->assertTrue(is_array($result));
        $this->assertArrayHasKey('version', $result);
        $this->assertArrayHasKey('statusCode', $result);
        $this->assertArrayHasKey('reason', $result);
        $this->assertArrayHasKey('headers', $result);
    }

    /**
     * @covers SoapHelper::parseResponseHeaders()
     */
    public function testParseResponseHeadersEmpty()
    {
        $result = SoapHelper::parseResponseHeaders(NULL);

        $this->assertTrue(is_array($result));
        $this->assertArrayHasKey('version', $result);
        $this->assertArrayHasKey('statusCode', $result);
        $this->assertArrayHasKey('reason', $result);
        $this->assertArrayHasKey('headers', $result);

        $this->assertNull($result['version']);
        $this->assertNull($result['statusCode']);
        $this->assertNull($result['reason']);
        $this->assertNull($result['headers']);
    }

}