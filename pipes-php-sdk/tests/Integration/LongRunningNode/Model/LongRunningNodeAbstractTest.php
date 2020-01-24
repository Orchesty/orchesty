<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\LongRunningNode\Model;

use PipesPhpSdkTests\DatabaseTestCaseAbstract;
use PipesPhpSdkTests\Integration\Application\TestNullApplication;

/**
 * Class LongRunningNodeAbstractTest
 *
 * @package PipesPhpSdkTests\Integration\LongRunningNode\Model
 */
final class LongRunningNodeAbstractTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Model\LongRunningNodeAbstract::setApplication
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Model\LongRunningNodeAbstract::getApplicationKey
     */
    public function testLongRunningNode(): void
    {
        $nullNode = new NullLongRunningNode();
        self::assertNull($nullNode->getApplicationKey());

        $nullNode->setApplication(new TestNullApplication());
        self::assertEquals('null-key', $nullNode->getApplicationKey());
    }

}
