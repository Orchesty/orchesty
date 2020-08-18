<?php declare(strict_types=1);

namespace DemoTests\Integration\CustomNode;

use Demo\CustomNode\DummyExceptionConnector;
use DemoTests\KernelTestCaseAbstract;
use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Monolog\Logger;

/**
 * Class DummyExceptionConnectorTest
 *
 * @package DemoTests\Integration\CustomNode
 */
final class DummyExceptionConnectorTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Demo\CustomNode\DummyExceptionConnector
     * @covers \Demo\CustomNode\DummyExceptionConnector::process
     * @covers \Demo\CustomNode\DummyExceptionConnector::throwDummyException
     * @covers \Demo\CustomNode\DummyExceptionConnector::setLogger
     * @throws Exception
     */
    public function testProcess(): void
    {
        $this->getFunctionMock('Demo\CustomNode', 'mt_rand')
            ->expects(self::exactly(1))
            ->willReturnOnConsecutiveCalls(...[5]);

        /** @var DummyExceptionConnector $connector */
        $connector = self::$container->get('hbpf.custom_node.dummy-exception');
        $connector->setLogger(new Logger('logger'));
        $dto = $connector->process(new ProcessDto());

        self::assertEquals('{}', $dto->getData());
    }

}
