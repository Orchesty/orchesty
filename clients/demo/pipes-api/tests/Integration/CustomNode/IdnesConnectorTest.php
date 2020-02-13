<?php declare(strict_types=1);

namespace DemoTests\Integration\CustomNode;

use Demo\CustomNode\IdnesConnector;
use DemoTests\KernelTestCaseAbstract;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;

/**
 * Class IdnesConnectorTest
 *
 * @package DemoTests\Integration\CustomNode
 */
class IdnesConnectorTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Demo\CustomNode\IdnesConnector
     * @covers \Demo\CustomNode\IdnesConnector::process
     * @throws CurlException
     */
    public function testProcess(): void
    {
        /** @var IdnesConnector $connector */
        $connector = self::$container->get('hbpf.custom_node.idnes');

        $dto = $connector->process(new ProcessDto());
        self::assertEquals('{}', $dto->getData());
    }

}
