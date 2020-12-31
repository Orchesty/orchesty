<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\RabbitMq\Impl\Batch;

use Exception;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchActionAbstract;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchTrait;
use InvalidArgumentException;
use PhpAmqpLib\Message\AMQPMessage;
use PipesPhpSdkTests\KernelTestCaseAbstract;
use RabbitMqBundle\Utils\Message;

/**
 * Class BatchActionAbstractTest
 *
 * @package PipesPhpSdkTests\Unit\RabbitMq\Impl\Batch
 */
final class BatchActionAbstractTest extends KernelTestCaseAbstract
{

    use BatchTrait;

    /**
     * @var callable
     */
    private $callback;

    /**
     *
     */
    public function setUp(): void
    {
        $this->callback = static function (): void {
            self::assertFake();
        };
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchActionAbstract
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchActionAbstract::isEmpty
     * @throws Exception
     */
    public function testValidateHeaders(): void
    {
        /** @var BatchActionAbstract $batchAction */
        $batchAction = $this->getMockForAbstractClass(BatchActionAbstract::class);
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('Missing "node-name" in the message header.');
        $batchAction->batchAction($this->createMessage(), $this->callback);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchActionAbstract::batchAction
     * @throws Exception
     */
    public function testBatchAction(): void
    {
        $node = self::createMock(BatchInterface::class);
        $node->method('processBatch')->willReturn($this->createPromise());

        $batchAction = $this->getMockForAbstractClass(BatchActionAbstract::class);
        $batchAction->method('getBatchService')->willReturn($node);
        $batchAction->batchAction($this->createMessage(['pf-node-name' => 'abc']), $this->callback);

        self::assertFake();
    }

    /**
     * @param mixed[] $headers
     * @param string  $content
     *
     * @return AMQPMessage
     */
    private function createMessage(array $headers = [], string $content = ''): AMQPMessage
    {
        return Message::create($content, $headers);
    }

}
