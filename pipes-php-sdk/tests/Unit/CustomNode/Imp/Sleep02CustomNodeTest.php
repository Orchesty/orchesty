<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\CustomNode\Imp;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\CustomNode\Impl\Sleep02CustomNode;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class Sleep02CustomNodeTest
 *
 * @package PipesPhpSdkTests\Unit\CustomNode\Imp
 */
final class Sleep02CustomNodeTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\Impl\Sleep02CustomNode::process
     */
    public function testProcess(): void
    {
        $start = microtime(TRUE);
        (new Sleep02CustomNode())->process(new ProcessDto());
        $time = microtime(TRUE) - $start;
        self::assertGreaterThan(0.2, $time);
    }

}
