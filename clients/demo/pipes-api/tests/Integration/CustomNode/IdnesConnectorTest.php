<?php declare(strict_types=1);

namespace DemoTests\Integration\CustomNode;

use Demo\Connector\IdnesConnector;
use DemoTests\KernelTestCaseAbstract;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;

/**
 * Class IdnesConnectorTest
 *
 * @package DemoTests\Integration\CustomNode
 */
final class IdnesConnectorTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Demo\Connector\IdnesConnector
     * @covers \Demo\Connector\IdnesConnector::processAction
     * @throws CurlException
     */
    public function testProcess(): void
    {
        /** @var IdnesConnector $connector */
        $connector = self::getContainer()->get('hbpf.idnes-connector');

        $dto = $connector->processAction((new ProcessDto())->setData('{}'));
        self::assertEquals('{}', $dto->getData());
    }

}
