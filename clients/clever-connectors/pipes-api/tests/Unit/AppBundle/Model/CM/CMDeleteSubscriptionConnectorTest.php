<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\CM;

use CleverConnectors\AppBundle\Model\CM\SubscriptionConnector\CMDeleteSubscriptionConnector;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Tests\KernelTestCaseAbstract;

/**
 * Class CMDeleteSubscriptionConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\CM
 */
final class CMDeleteSubscriptionConnectorTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testCMConnectors(): void
    {
        $curl = $this->createMock(CurlManagerInterface::class);
        $curl->method('send')->willReturn(new ResponseDto(200, '', 'someBody', []));
        $conn = new CMDeleteSubscriptionConnector($curl, ['cert' => '', 'ca' => '']);

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