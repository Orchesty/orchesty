<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\CustomNode\Imp;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\CustomNode\Impl\Sleep01CustomNode;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class Sleep01CustomNodeTest
 *
 * @package PipesPhpSdkTests\Unit\CustomNode\Imp
 */
final class Sleep01CustomNodeTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\Impl\Sleep01CustomNode::process
     */
    public function testProcess(): void
    {
        $start = microtime(TRUE);
        (new Sleep01CustomNode())->process(new ProcessDto());
        $time = microtime(TRUE) - $start;
        self::assertGreaterThan(0.1, $time);
    }

}
