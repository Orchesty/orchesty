<?php declare(strict_types=1);

namespace DemoTests\Integration\CustomNode;

use Demo\CustomNode\DummyExceptionConnector;
use DemoTests\KernelTestCaseAbstract;
use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class DummyExceptionConnectorTest
 *
 * @package DemoTests\Integration\CustomNode
 */
#[CoversClass(DummyExceptionConnector::class)]
final class DummyExceptionConnectorTest extends KernelTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testProcess(): void
    {
        /** @var DummyExceptionConnector $connector */
        $connector = self::getContainer()->get('hbpf.custom_node.dummy-exception');
        $connector->setLogger(new Logger('logger'));
        $dto = $connector->processAction((new ProcessDto())->setData('{}'));

        self::assertEquals('{}', $dto->getData());
    }

}
