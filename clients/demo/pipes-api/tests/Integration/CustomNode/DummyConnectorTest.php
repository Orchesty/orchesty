<?php declare(strict_types=1);

namespace DemoTests\Integration\CustomNode;

use Demo\CustomNode\DummyConnector;
use DemoTests\KernelTestCaseAbstract;
use Hanaboso\CommonsBundle\Process\ProcessDto;

/**
 * Class DummyConnectorTest
 *
 * @package DemoTests\Integration\CustomNode
 */
final class DummyConnectorTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Demo\CustomNode\DummyConnector::process
     */
    public function testProcess(): void
    {
        /** @var DummyConnector $connector */
        $connector = self::$container->get('hbpf.custom_node.print-label');

        $dto = $connector->process((new ProcessDto())->setData('{"foo":"bar"}'));
        self::assertEquals('{"foo":"bar"}', $dto->getData());
    }

}
