<?php declare(strict_types=1);

namespace Tests\Live\AppBundle\Model\CM;

use CleverConnectors\AppBundle\Model\CM\SubscriberConnector\CMDeleteSubscriberConnector;
use GuzzleHttp\Client;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlClientFactory;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Tests\KernelTestCaseAbstract;

/**
 * Class CMDeleteSubscriberConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\CM
 */
final class CMDeleteSubscriberConnectorTest extends KernelTestCaseAbstract
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
        $conn = new CMDeleteSubscriberConnector($curl);

        $conn->processAction((new ProcessDto())->setData('{"email":"eml@eml.com"}')->setHeaders([
            'pf_token' => '-3*QYg*3H-5+vaez_K7_N-4K1YhCn88k',
            'pf_guid'  => '51a83cfe-9e04-11e7-a177-000d3a20eb16',
        ]));
    }

}