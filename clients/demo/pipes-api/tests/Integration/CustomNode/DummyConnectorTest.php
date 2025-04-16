<?php declare(strict_types=1);

namespace DemoTests\Integration\CustomNode;

use Demo\CustomNode\DummyConnector;
use DemoTests\KernelTestCaseAbstract;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class DummyConnectorTest
 *
 * @package DemoTests\Integration\CustomNode
 */
#[CoversClass(DummyConnector::class)]
final class DummyConnectorTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Demo\CustomNode\DummyConnector::processAction
     */
    public function testProcess(): void
    {
        /** @var DummyConnector $connector */
        $connector = self::getContainer()->get('hbpf.custom_node.print-label');

        $dto = $connector->processAction((new ProcessDto())->setData('{"foo":"bar"}'));
        self::assertSame('{"foo":"bar"}', $dto->getData());
    }

}
