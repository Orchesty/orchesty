<?php declare(strict_types=1);

namespace Tests\Integration\Commons\Transport\Curl;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlException;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Tests\KernelTestCaseAbstract;

/**
 * Class CurlManagerTest
 *
 * @package Tests\Integration\Commons\Transport\Curl
 */
class CurlManagerTest extends KernelTestCaseAbstract
{

    /** @var CurlManager */
    private $curl;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->curl = $this->container->get('hbpf.transport.curl_manager');
    }

    /**
     *
     */
    public function testSend(): void
    {
        $requestDto = (new RequestDto(CurlManager::METHOD_GET, new Uri('https://google.cz')))
            ->setHeaders(['Cache-Control' => 'private, max-age=0']);
        $this->assertEquals(200, $this->curl->send($requestDto)->getStatusCode());
    }

    /**
     *
     */
    public function testSendNotFound(): void
    {
        $this->expectException(CurlException::class);
        $this->expectExceptionCode(303);

        $requestDto = new RequestDto(CurlManager::METHOD_GET, new Uri('some-unknown-address'));
        $this->curl->send($requestDto)->getStatusCode();
    }

}