<?php declare(strict_types=1);

namespace DemoTests\Integration\CustomNode;

use Demo\CustomNode\SplitFileBatch;
use DemoTests\KernelTestCaseAbstract;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\Utils\Exception\DateTimeException;

/**
 * Class SplitFileBatchTest
 *
 * @package DemoTests\Integration\CustomNode
 */
final class SplitFileBatchTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Demo\CustomNode\SplitFileBatch::processBatch
     * @covers \Demo\CustomNode\SplitFileBatch::process
     * @throws DateTimeException
     */
    public function testProcessBatch(): void
    {
        /** @var SplitFileBatch $customNode */
        $customNode = self::$container->get('hbpf.custom_node.split-file');
        $dto        = $customNode->process(new ProcessDto());

        $customNode->processBatch(
            $dto,
            static function (): void {
                self::assertTrue(TRUE);
            }
        )->then(
            static function (): void {
                self::assertTrue(TRUE);
            }
        )->wait();

        $customNode->processBatch(
            (new ProcessDto())->setData('{"data":{"bids":"something","asks":"something"}}'),
            static function (): void {
                self::assertTrue(TRUE);
            }
        )->then(
            static function (): void {
                self::assertTrue(TRUE);
            }
        )->wait();
    }

}
