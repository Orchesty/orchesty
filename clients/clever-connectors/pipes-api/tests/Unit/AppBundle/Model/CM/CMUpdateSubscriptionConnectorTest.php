<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\CM;

use CleverConnectors\AppBundle\Model\CM\SubscriptionConnector\CMUpdateSubscriptionConnector;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use GuzzleHttp\Client;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlClientFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Tests\KernelTestCaseAbstract;

/**
 * Class CMUpdateSubscriptionConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\CM
 */
final class CMUpdateSubscriptionConnectorTest extends KernelTestCaseAbstract
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
        $conn = new CMUpdateSubscriptionConnector($curl, []);

        $conn->processAction((new ProcessDto())->setData('{"email":"eml@eml.com"}')->setHeaders([
            'pf_token' => '-3*QYg*3H-5+vaez_K7_N-4K1YhCn88k',
            'pf_guid'  => '51a83cfe-9e04-11e7-a177-000d3a20eb16',
        ]));
    }

    /**
     *
     */
    public function testCMConnectors(): void
    {
        $curl = $this->createMock(CurlManagerInterface::class);
        $curl->method('send')->willReturn(new ResponseDto(200, '', 'someBody', []));
        $conn = new CMUpdateSubscriptionConnector($curl, ['cert' => '', 'ca' => '']);

        $res = $conn->processAction((new ProcessDto())
            ->setData('{"email":"eml@eml.com"}')
            ->setHeaders(
                [
                    CMHeaders::createKey(CMHeaders::TOKEN)      => 'ttoken',
                    CMHeaders::createKey(CMHeaders::GUID)       => 'gguid',
                    CMHeaders::createKey(CMHeaders::SYSTEM_KEY) => 'system',
                ]
            ));
        self::assertEquals('someBody', $res->getData());
    }

}