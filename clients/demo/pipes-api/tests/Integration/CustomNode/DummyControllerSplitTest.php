<?php declare(strict_types=1);

namespace DemoTests\Integration\CustomNode;

use Demo\CustomNode\DummyConnectorSplit;
use DemoTests\KernelTestCaseAbstract;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\Utils\Exception\DateTimeException;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class DummyControllerSplitTest
 *
 * @package DemoTests\Integration\CustomNode
 */
#[CoversClass(DummyConnectorSplit::class)]
final class DummyControllerSplitTest extends KernelTestCaseAbstract
{

    /**
     * @throws DateTimeException
     */
    public function testProcess(): void
    {
        /** @var DummyConnectorSplit $connector */
        $connector = self::getContainer()->get('hbpf.custom_node.fill-in-form');
        $dto       = $connector->processAction((new ProcessDto())->setData('{}'));

        self::assertNull($dto->getHeader('something'));
    }

}
