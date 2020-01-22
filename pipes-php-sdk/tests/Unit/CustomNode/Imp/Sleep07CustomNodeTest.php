<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\CustomNode\Imp;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\CustomNode\Impl\Sleep07CustomNode;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class Sleep07CustomNodeTest
 *
 * @package PipesPhpSdkTests\Unit\CustomNode\Imp
 */
final class Sleep07CustomNodeTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\Impl\Sleep07CustomNode::process
     */
    public function testProcess(): void
    {
        $start = microtime(TRUE);
        (new Sleep07CustomNode())->process(new ProcessDto());
        $time = microtime(TRUE) - $start;
        self::assertGreaterThan(0.7, $time);
    }

}
