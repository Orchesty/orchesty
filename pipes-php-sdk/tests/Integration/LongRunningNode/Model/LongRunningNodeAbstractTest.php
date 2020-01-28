<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\LongRunningNode\Model;

use Exception;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
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

    /**
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Model\LongRunningNodeAbstract::getApplication
     * @throws Exception
     */
    public function testGetApplicationException(): void
    {
        $nullNode = new NullLongRunningNode();
        self::expectException(ConnectorException::class);
        self::expectExceptionCode(ConnectorException::MISSING_APPLICATION);
        $nullNode->getApplication();
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Model\LongRunningNodeAbstract::getApplication
     * @throws Exception
     */
    public function testGetApplication(): void
    {
        $nullNode = new NullLongRunningNode();
        $nullNode->setApplication(new TestNullApplication());
        self::assertNotEmpty($nullNode->getApplication());
    }

}
