<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\LongRunningNode\Consumer;

use Exception;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\Utils\System\PipesHeaders;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use PipesPhpSdkTests\DatabaseTestCaseAbstract;
use RabbitMqBundle\Connection\Connection;

/**
 * Class LongRunningNodeCallbackTest
 *
 * @package PipesPhpSdkTests\Integration\LongRunningNode\Consumer
 */
final class LongRunningNodeCallbackTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Consumer\LongRunningNodeCallback
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Consumer\LongRunningNodeCallback::processMessage

     * @throws Exception
     */
    public function testProcessMessage(): void
    {
        $node = self::$container->get('hanaboso.pipes_framework.long_running_node.callback');

        $connection = self::createPartialMock(Connection::class, ['getChannel']);
        $connection->expects(self::any())->method('getChannel');

        $ampq = new AMQPMessage(
            '',
            [
                'application_headers' => new AMQPTable([PipesHeaders::createKey(PipesHeaders::NODE_NAME) => 'null']),
            ]
        );
        $this->setProperty($ampq, 'delivery_info', ['delivery_tag' => 2]);
        $node->processMessage(
            $ampq,
            $connection,
            2
        );

        self::assertFake();
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Consumer\LongRunningNodeCallback::processMessage

     * @throws Exception
     */
    public function testProcessMessageErr(): void
    {
        $node = self::$container->get('hanaboso.pipes_framework.long_running_node.callback');

        $connection = self::createPartialMock(Connection::class, ['getChannel']);
        $connection->expects(self::any())->method('getChannel');

        $ampq = new AMQPMessage(
            '',
            [
                'application_headers' => new AMQPTable([PipesHeaders::createKey(PipesHeaders::NODE_NAME) => 'null']),
            ]
        );

        self::expectException(OnRepeatException::class);
        $node->processMessage(
            $ampq,
            $connection,
            2
        );
    }

}
