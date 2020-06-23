<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\RabbitMq\Impl\Batch;

use Exception;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchActionAbstract;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchTrait;
use Monolog\Logger;
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
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchActionAbstract::setLogger
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchActionAbstract::validateHeaders
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchActionAbstract::isEmpty
     * @throws Exception
     */
    public function testValidateHeaders(): void
    {
        /** @var BatchActionAbstract $batchAction */
        $batchAction = $this->getMockForAbstractClass(BatchActionAbstract::class);
        $batchAction->setLogger(new Logger('logger'));
        $batchAction
            ->batchAction($this->createMessage(), $this->callback)
            ->then(
                NULL,
                static function (Exception $e): void {
                    self::assertSame('Missing "node-name" in the message header.', $e->getMessage());
                }
            )->wait();

        self::assertFake();
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchActionAbstract::validateHeaders
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchActionAbstract::batchAction
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchActionAbstract::createProcessDto
     * @throws Exception
     */
    public function testBatchAction(): void
    {
        $node = self::createMock(BatchInterface::class);
        $node->method('processBatch')->willReturn($this->createPromise());

        $batchAction = $this->getMockForAbstractClass(BatchActionAbstract::class);
        $batchAction->method('getBatchService')->willReturn($node);
        $batchAction->setLogger(new Logger('logger'));
        $batchAction
            ->batchAction($this->createMessage(['pf-node-name' => 'abc']), $this->callback)
            ->then(
                static function (): void {
                    self::assertTrue(TRUE);
                },
                static function ($throwable): void {
                    self::fail(sprintf('%s%s%s', $throwable->getMessage(), PHP_EOL, $throwable->getTraceAsString()));
                }
            )->wait();

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
