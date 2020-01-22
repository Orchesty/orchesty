<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\RabbitMq\Impl\Batch;

use Exception;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchActionAbstract;
use PhpAmqpLib\Message\AMQPMessage;
use PipesPhpSdkTests\KernelTestCaseAbstract;
use RabbitMqBundle\Utils\Message;
use React\EventLoop\Factory;
use Throwable;

/**
 * Class BatchActionAbstractTest
 *
 * @package PipesPhpSdkTests\Unit\RabbitMq\Impl\Batch
 */
final class BatchActionAbstractTest extends KernelTestCaseAbstract
{

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
        };
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchActionAbstract::validateHeaders()
     * @throws Exception
     */
    public function testValidateHeaders(): void
    {
        $loop = Factory::create();

        /** @var BatchActionAbstract $batchAction */
        $batchAction = $this->getMockForAbstractClass(BatchActionAbstract::class);

        $batchAction
            ->batchAction($this->createMessage(), $loop, $this->callback)
            ->then(
                NULL,
                static function (Exception $e) use ($loop): void {
                    self::assertSame('Missing "node-name" in the message header.', $e->getMessage());
                    $loop->stop();
                }
            )->done();

        $loop->run();
        self::assertFake();
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchActionAbstract::validateHeaders()
     * @throws Exception
     */
    public function testBatchAction(): void
    {
        $loop = Factory::create();

        /** @var BatchActionAbstract $batchAction */
        $batchAction = $this->getMockForAbstractClass(BatchActionAbstract::class);

        $batchAction
            ->batchAction($this->createMessage(['pf-node-name' => 'abc']), $loop, $this->callback)
            ->then(
                static function () use ($loop): void {
                    self::assertTrue(TRUE);

                    $loop->stop();
                },
                static function (Throwable $throwable): void {
                    self::fail(sprintf('%s%s%s', $throwable->getMessage(), PHP_EOL, $throwable->getTraceAsString()));
                }
            )->done();

        $loop->run();
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
