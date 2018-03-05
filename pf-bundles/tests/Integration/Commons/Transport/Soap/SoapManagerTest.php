<?php declare(strict_types=1);

namespace Tests\Integration\Commons\Transport\Soap;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Soap\Dto\Wsdl\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Soap\SoapException;
use Hanaboso\PipesFramework\Commons\Transport\Soap\SoapManager;
use Tests\KernelTestCaseAbstract;

/**
 * Class SoapManagerTest
 *
 * @package Tests\Integration\Commons\Transport\Soap
 */
class SoapManagerTest extends KernelTestCaseAbstract
{

    /**
     * @var SoapManager
     */
    private $soap;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->soap = $this->container->get('hbpf.transport.soap_manager');
    }

    /**
     *
     */
    public function testSendInvalidWsdl(): void
    {
        $this->expectException(SoapException::class);
        $this->expectExceptionCode(SoapException::INVALID_WSDL);

        $requestDto = (new RequestDto('function', [], 'namespcae', new Uri('http://google.cz')))->setVersion(1);
        $this->assertEquals(200, $this->soap->send($requestDto)->getLastResponseHeaders());
    }

}