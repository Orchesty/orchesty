<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\CM;

use CleverConnectors\AppBundle\Model\CM\SubscriptionConnector\CMDeleteSubscriptionConnector;
use GuzzleHttp\Client;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlClientFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Tests\KernelTestCaseAbstract;

/**
 * Class CMDeleteSubscriptionConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\CM
 */
class CMDeleteSubscriptionConnectorTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testRealConnect(): void
    {
        $this->markTestSkipped('Online test');
        $opt = [
            'curl' => [
                CURLOPT_SSL_VERIFYHOST => FALSE,
                CURLOPT_SSL_VERIFYPEER => FALSE,
            ],
        ];

        $fac = $this->createMock(CurlClientFactory::class);
        $fac->expects($this->at(0))->method('create')->willReturn(new Client($opt));
        $curl = new CurlManager($fac);
        $conn = new CMDeleteSubscriptionConnector($curl);

        $res = $conn->processAction((new ProcessDto())->setData('{"email":"eml@eml.com"}')->setHeaders([
            'token' => '-3*QYg*3H-5+vaez_K7_N-4K1YhCn88k',
            'guid'  => '51a83cfe-9e04-11e7-a177-000d3a20eb16',
        ]));
    }

    /**
     *
     */
    public function testCMConnectors(): void
    {
        $curl = $this->createMock(CurlManagerInterface::class);
        $curl->method('send')->willReturn(new ResponseDto(200, '', 'someBody', []));
        $conn = new CMDeleteSubscriptionConnector($curl);

        $res = $conn->processAction((new ProcessDto())->setData('{"email":"eml@eml.com"}')->setHeaders([
            'token' => 'ttoken', 'guid' => 'gguid',
        ]));
        self::assertEquals('someBody', $res->getData());
    }

}