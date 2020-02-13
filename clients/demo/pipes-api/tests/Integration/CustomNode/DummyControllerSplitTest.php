<?php declare(strict_types=1);

namespace DemoTests\Integration\CustomNode;

use Demo\CustomNode\DummyConnectorSplit;
use DemoTests\KernelTestCaseAbstract;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\Utils\Exception\DateTimeException;

/**
 * Class DummyControllerSplitTest
 *
 * @package DemoTests\Integration\CustomNode
 */
final class DummyControllerSplitTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Demo\CustomNode\DummyConnectorSplit::process
     * @throws DateTimeException
     */
    public function testProcess(): void
    {
        /** @var DummyConnectorSplit $connector */
        $connector = self::$container->get('hbpf.custom_node.fill-in-form');
        $dto       = $connector->process(new ProcessDto());

        self::assertNull($dto->getHeader('something'));
    }

}
