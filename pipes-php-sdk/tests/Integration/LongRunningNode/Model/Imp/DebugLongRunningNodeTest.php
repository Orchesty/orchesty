<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\LongRunningNode\Model\Imp;

use Hanaboso\PipesPhpSdk\LongRunningNode\Model\Impl\DebugLongRunningNode;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class DebugLongRunningNodeTest
 *
 * @package PipesPhpSdkTests\Integration\LongRunningNode\Model\Imp
 */
final class DebugLongRunningNodeTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testGetId(): void
    {
        self::assertEquals('debug', (new DebugLongRunningNode())->getId());
    }

}
