<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\CustomNode\Imp;

use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Database\Document\Embed\EmbedNode;
use Hanaboso\PipesPhpSdk\Database\Document\Node;
use Hanaboso\Utils\System\PipesHeaders;
use InvalidArgumentException;
use Monolog\Logger;
use PhpAmqpLib\Channel\AMQPChannel;
use PipesPhpSdkTests\DatabaseTestCaseAbstract;
use PipesPhpSdkTests\Integration\Application\TestNullApplication;
use RabbitMqBundle\Connection\Connection;
use RabbitMqBundle\Publisher\Publisher;
use ReflectionException;

/**
 * Class RabbitCustomNodeTest
 *
 * @package PipesPhpSdkTests\Integration\CustomNode\Imp
 */
final class RabbitCustomNodeTest extends DatabaseTestCaseAbstract
{

    /**
     * @var TestNullRabbitNode
     */
    private TestNullRabbitNode $nullConnector;

    /**
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\CustomNodeAbstract::getApplicationKey
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\CustomNodeAbstract::setApplication
     */
    public function testGetApplicationKey(): void
    {
        $key = $this->nullConnector->getApplicationKey();
        self::assertNull($key);

        $this->nullConnector->setApplication(new TestNullApplication());
        $key = $this->nullConnector->getApplicationKey();
        self::assertEquals('null-key', $key);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\Impl\RabbitCustomNode
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\Impl\RabbitCustomNode::setLogger
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\Impl\RabbitCustomNode::publishMessage
     *
     * @throws ReflectionException
     */
    public function testPublishMessage(): void
    {
        $this->setProperty($this->nullConnector, 'chann', 3);
        $this->setProperty($this->nullConnector, 'queues', [1, 2]);
        $this->invokeMethod($this->nullConnector, 'publishMessage', [['message'], ['headers']]);

        self::assertTrue(TRUE);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\Impl\RabbitCustomNode::validate
     *
     * @throws ReflectionException
     */
    public function testValidateEmptyNodeId(): void
    {
        $dto = new ProcessDto();
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('Missing "pf-node-id" in the message header.');
        $this->invokeMethod($this->nullConnector, 'validate', [$dto]);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\Impl\RabbitCustomNode::isEmpty
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\Impl\RabbitCustomNode::validate
     *
     * @throws ReflectionException
     */
    public function testValidateEmptyTopologyId(): void
    {
        $dto = (new ProcessDto())->setHeaders([PipesHeaders::createKey(PipesHeaders::NODE_ID) => '123']);
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('Missing "pf-topology-id" in the message header.');
        $this->invokeMethod($this->nullConnector, 'validate', [$dto]);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\Impl\RabbitCustomNode::isEmpty
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\Impl\RabbitCustomNode::validate
     *
     * @throws ReflectionException
     */
    public function testValidateEmptyCorrelationId(): void
    {
        $dto = (new ProcessDto())->setHeaders(
            [
                PipesHeaders::createKey(PipesHeaders::NODE_ID)     => '123',
                PipesHeaders::createKey(PipesHeaders::TOPOLOGY_ID) => '456',
            ]
        );
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('Missing "pf-correlation-id" in the message header.');
        $this->invokeMethod($this->nullConnector, 'validate', [$dto]);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\Impl\RabbitCustomNode::isEmpty
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\Impl\RabbitCustomNode::validate
     *
     * @throws ReflectionException
     */
    public function testValidateEmptyProcessId(): void
    {
        $dto = (new ProcessDto())->setHeaders(
            [
                PipesHeaders::createKey(PipesHeaders::NODE_ID)        => '123',
                PipesHeaders::createKey(PipesHeaders::TOPOLOGY_ID)    => '456',
                PipesHeaders::createKey(PipesHeaders::CORRELATION_ID) => '789',
            ]
        );
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('Missing "pf-process-id" in the message header.');
        $this->invokeMethod($this->nullConnector, 'validate', [$dto]);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\Impl\RabbitCustomNode::isEmpty
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\Impl\RabbitCustomNode::validate
     *
     * @throws ReflectionException
     */
    public function testValidateEmptyParentId(): void
    {
        $dto = (new ProcessDto())->setHeaders(
            [
                PipesHeaders::createKey(PipesHeaders::NODE_ID)        => '123',
                PipesHeaders::createKey(PipesHeaders::TOPOLOGY_ID)    => '456',
                PipesHeaders::createKey(PipesHeaders::CORRELATION_ID) => '789',
                PipesHeaders::createKey(PipesHeaders::PROCESS_ID)     => '147',
            ]
        );
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('Missing "pf-parent-id" in the message header.');
        $this->invokeMethod($this->nullConnector, 'validate', [$dto]);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\Impl\RabbitCustomNode::isEmpty
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\Impl\RabbitCustomNode::validate
     *
     * @throws ReflectionException
     */
    public function testValidate(): void
    {
        $dto = (new ProcessDto())->setHeaders(
            [
                PipesHeaders::createKey(PipesHeaders::NODE_ID)        => '123',
                PipesHeaders::createKey(PipesHeaders::TOPOLOGY_ID)    => '456',
                PipesHeaders::createKey(PipesHeaders::CORRELATION_ID) => '789',
                PipesHeaders::createKey(PipesHeaders::PROCESS_ID)     => '147',
                PipesHeaders::createKey(PipesHeaders::PARENT_ID)      => '369',
            ]
        );
        $this->invokeMethod($this->nullConnector, 'validate', [$dto]);
        self::assertTrue(TRUE);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\Impl\RabbitCustomNode::normalizeHeaders
     *
     * @throws ReflectionException
     */
    public function testNormalizeHeadersTest(): void
    {
        $dto = (new ProcessDto())->setHeaders(
            [
                PipesHeaders::createKey(PipesHeaders::NODE_ID)     => [1 => ['a']],
                PipesHeaders::createKey(PipesHeaders::TOPOLOGY_ID) => '456',
                PipesHeaders::createKey(PipesHeaders::PARENT_ID)   => '369',
            ]
        );

        $this->invokeMethod($this->nullConnector, 'normalizeHeaders', [$dto]);

        self::assertTrue(TRUE);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\Impl\RabbitCustomNode::bindChannels
     *
     * @throws ReflectionException
     * @throws Exception
     */
    public function testBindChannels(): void
    {
        $embedNode = new EmbedNode();
        $embedNode->setName('name');
        $this->invokeMethod($embedNode, 'setId', ['1']);
        $this->pfd($embedNode);

        $node = (new Node())->setNext([$embedNode]);
        $this->pfd($node);

        $dto = (new ProcessDto())->setHeaders(
            [
                PipesHeaders::createKey(PipesHeaders::NODE_ID)        => $node->getId(),
                PipesHeaders::createKey(PipesHeaders::TOPOLOGY_ID)    => '456',
                PipesHeaders::createKey(PipesHeaders::CORRELATION_ID) => '789',
                PipesHeaders::createKey(PipesHeaders::PROCESS_ID)     => '147',
                PipesHeaders::createKey(PipesHeaders::PARENT_ID)      => '369',
            ]
        );

        $this->invokeMethod($this->nullConnector, 'bindChannels', [$dto]);
        self::assertTrue(TRUE);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\Impl\RabbitCustomNode::unbindChannels
     *
     * @throws ReflectionException
     */
    public function testUnbindChannels(): void
    {
        $this->setProperty($this->nullConnector, 'chann', 3);
        $this->setProperty($this->nullConnector, 'queues', [1, 2]);
        $this->invokeMethod($this->nullConnector, 'unbindChannels');

        self::assertTrue(TRUE);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\Impl\RabbitCustomNode::process()
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\Impl\RabbitCustomNode::validate()
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\Impl\RabbitCustomNode::normalizeHeaders()
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\Impl\RabbitCustomNode::bindChannels()
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\Impl\RabbitCustomNode::processBatch()
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\Impl\RabbitCustomNode::unbindChannels()
     *
     * @throws LockException
     * @throws MappingException
     * @throws Exception
     */
    public function testProcess(): void
    {
        $node = new Node();
        $this->pfd($node);

        $dto = (new ProcessDto())->setHeaders(
            [
                PipesHeaders::createKey(PipesHeaders::NODE_ID)        => $node->getId(),
                PipesHeaders::createKey(PipesHeaders::TOPOLOGY_ID)    => '456',
                PipesHeaders::createKey(PipesHeaders::CORRELATION_ID) => '789',
                PipesHeaders::createKey(PipesHeaders::PROCESS_ID)     => '147',
                PipesHeaders::createKey(PipesHeaders::PARENT_ID)      => '369',
                'test'                                                => ['a' => 'a'],
            ]
        );

        $dto = $this->nullConnector->process($dto);
        self::assertEquals(5, count($dto->getHeaders()));
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $channel = self::createPartialMock(AMQPChannel::class, ['basic_publish', 'queue_bind', 'queue_unbind']);
        $channel->expects(self::any())->method('basic_publish');
        $channel->expects(self::any())->method('queue_bind')->willReturn('mock');
        $channel->expects(self::any())->method('queue_unbind')->willReturn('mock');

        $connection = self::createPartialMock(Connection::class, ['getChannel', 'createChannel']);
        $connection->expects(self::any())->method('createChannel')->willReturn(2);
        $connection->expects(self::any())->method('getChannel')->willReturn($channel);

        $publisher = self::createPartialMock(Publisher::class, ['getExchange']);
        $publisher->expects(self::any())->method('getExchange')->willReturn('exchange');

        $this->nullConnector = new TestNullRabbitNode($this->dm, $connection, $publisher);
        $this->nullConnector->setLogger(new Logger('logger'));
    }

}
