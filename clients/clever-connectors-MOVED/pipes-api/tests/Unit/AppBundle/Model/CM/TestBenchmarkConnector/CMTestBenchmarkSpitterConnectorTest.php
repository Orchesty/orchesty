<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\CM\TestBenchmarkConnector;

use CleverConnectors\AppBundle\Model\CM\TestBenchmarkConnector\CMTestBenchmarkSpitterConnector;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Tests\KernelTestCaseAbstract;

/**
 * Created by PhpStorm.
 * User: lukas.hlavac
 * Date: 1/17/18
 * Time: 9:56 AM
 */
class CMTestBenchmarkSpitterConnectorTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testProcessAction(): void
    {
        $processDto = new ProcessDto();

        $curl = $this->createMock(CurlManagerInterface::class);
        $curl->method('send')->will($this->returnCallback(function (RequestDto $requestDto) use ($processDto) {
            $expt = new RequestDto(
                CurlManager::METHOD_POST,
                new Uri('spitterhost/blackhole')
            );
            $expt->setBody($processDto->getData());

            self::assertEquals($expt, $requestDto);

            return new ResponseDto(200, '', 'someBody', []);
        }));

        $conn = new CMTestBenchmarkSpitterConnector($curl, 'spitterhost');

        $res = $conn->processAction($processDto);

        self::assertEquals('someBody', $res->getData());
    }

}