<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\CM\SubscriptionConnector;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\SubscriptionConnector\CMCreateSubscriptionConnector;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Tests\KernelTestCaseAbstract;

/**
 * Class CMCreateSubscriptionConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\CM\SubscriptionConnector
 */
final class CMCreateSubscriptionConnectorTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testCMConnectors(): void
    {
        $curl = $this->createMock(CurlManagerInterface::class);
        $curl->method('send')->willReturn(new ResponseDto(200, '', 'someBody', []));
        $conn = new CMCreateSubscriptionConnector($curl);

        $res = $conn->processAction((new ProcessDto())
            ->setData('{"data":[]}')
            ->setHeaders(
                [
                    CMHeaders::createKey(CMHeaders::TOKEN)      => 'ttoken',
                    CMHeaders::createKey(CMHeaders::GUID)       => 'gguid',
                    CMHeaders::createKey(CMHeaders::SYSTEM_KEY) => 'system',
                ]
            ));
        self::assertEquals('someBody', $res->getData());
    }

    /**
     *
     */
    public function testCMConnectorsMissingGuid(): void
    {
        $conn = $this->container->get('hbpf.connector.cleverconnectors-create-subscriptions-connector');
        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);
        $conn->processAction((new ProcessDto())->setHeaders(['token' => 'token']));
    }

    /**
     *
     */
    public function testCMConnectorsMissingToken(): void
    {
        $conn = $this->container->get('hbpf.connector.cleverconnectors-create-subscriptions-connector');
        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);
        $conn->processAction((new ProcessDto())->setHeaders(['guis' => 'guid']));
    }

}