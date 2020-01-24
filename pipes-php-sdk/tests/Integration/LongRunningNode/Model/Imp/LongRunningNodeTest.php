<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\LongRunningNode\Model\Imp;

use Exception;
use Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData;
use PhpAmqpLib\Message\AMQPMessage;
use PipesPhpSdkTests\DatabaseTestCaseAbstract;

/**
 * Class LongRunningNodeTest
 *
 * @package PipesPhpSdkTests\Integration\LongRunningNode\Model\Imp
 */
final class LongRunningNodeTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Model\Impl\LongRunningNodeAbstract::beforeAction
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Model\Impl\LongRunningNodeAbstract::afterAction
     * @throws Exception
     */
    public function testBeforeAction(): void
    {
        $node = new NullLongRunningNode();
        self::assertEquals('', $node->beforeAction(new AMQPMessage())->getData());
        self::assertEquals('data', $node->afterAction((new LongRunningNodeData())->setData('data'), [])->getData());
    }

}
