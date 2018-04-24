<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\CM\SubscriberConnector;

use CleverConnectors\AppBundle\Model\CM\SubscriberConnector\CMDeleteSubscriberConnector;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Tests\KernelTestCaseAbstract;

/**
 * Class CMDeleteSubscriberConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\CM\SubscriberConnector
 */
final class CMDeleteSubscriberConnectorTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testCMConnectors(): void
    {
        $curl = $this->createMock(CurlManagerInterface::class);
        $curl->method('send')->willReturn(new ResponseDto(200, '', 'someBody', []));
        $conn = new CMDeleteSubscriberConnector($curl);

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