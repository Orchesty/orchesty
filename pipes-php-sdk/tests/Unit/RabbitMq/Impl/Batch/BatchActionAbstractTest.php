<?php declare(strict_types=1);

namespace Tests\Unit\RabbitMq\Impl\Batch;

use Exception;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchActionAbstract;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\TestCase;
use RabbitMqBundle\Utils\Message;
use React\EventLoop\Factory;
use Throwable;

/**
 * Class BatchActionAbstractTest
 *
 * @package Tests\Unit\RabbitMq\Impl\Batch
 */
final class BatchActionAbstractTest extends TestCase
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
        $this->callback = function (): void {

        };
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
                function (Exception $e) use ($loop): void {
                    self::assertInstanceOf(Exception::class, $e);
                    self::assertSame('Missing "node-name" in the message header.', $e->getMessage());
                    $loop->stop();
                }
            )->done();

        $loop->run();
        self::assertEmpty([]);
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
                function () use ($loop): void {
                    self::assertTrue(TRUE);

                    $loop->stop();
                },
                function (Throwable $throwable): void {
                    self::fail(sprintf('%s%s%s', $throwable->getMessage(), PHP_EOL, $throwable->getTraceAsString()));
                }
            )->done();

        $loop->run();
        self::assertEmpty([]);
    }

}
