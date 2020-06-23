<?php declare(strict_types=1);

namespace DemoTests\Integration\CustomNode;

use Demo\CustomNode\BatchCustomNode;
use DemoTests\KernelTestCaseAbstract;
use Hanaboso\CommonsBundle\Process\ProcessDto;

/**
 * Class BatchCustomNodeTest
 *
 * @package DemoTests\Integration\CustomNode
 */
final class BatchCustomNodeTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Demo\CustomNode\BatchCustomNode::processBatch
     */
    public function testProcessBatch(): void
    {
        /** @var BatchCustomNode $customNode */
        $customNode = self::$container->get('hbpf.custom_node.batch');
        $customNode->processBatch(
            new ProcessDto(),
            static function (): void {
                self::assertTrue(TRUE);
            }
        )->then(
            static function (): void {
                self::assertTrue(TRUE);
            }
        )->wait();
    }

    /**
     * @covers \Demo\CustomNode\BatchCustomNode::process
     */
    public function testProcess(): void
    {
        /** @var BatchCustomNode $customNode */
        $customNode = self::$container->get('hbpf.custom_node.batch');

        /** @var ProcessDto $result */
        $result = $customNode->process((new ProcessDto())->setData('{"foo":"bar"}'));

        self::assertEquals('{"foo":"bar"}', $result->getData());
    }

}
