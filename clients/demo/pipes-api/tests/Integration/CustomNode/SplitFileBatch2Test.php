<?php declare(strict_types=1);

namespace DemoTests\Integration\CustomNode;

use Demo\CustomNode\SplitFileBatch2;
use DemoTests\KernelTestCaseAbstract;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use ReflectionException;

/**
 * Class SplitFileBatch2Test
 *
 * @package DemoTests\Integration\CustomNode
 */
final class SplitFileBatch2Test extends KernelTestCaseAbstract
{

    /**
     * @covers \Demo\CustomNode\SplitFileBatch2::processBatch
     * @throws ReflectionException
     */
    public function testProcessBatch(): void
    {
        $mock = self::createPartialMock(SplitFileBatch2::class, ['publishMessage']);
        $mock->expects(self::any())->method('publishMessage');

        $this->invokeMethod($mock, 'processBatch', [(new ProcessDto())->setData('{"data":{"bids":1}}')]);

        self::assertFake();
    }

}
