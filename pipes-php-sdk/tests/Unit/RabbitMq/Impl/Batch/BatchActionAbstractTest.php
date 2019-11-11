<?php declare(strict_types=1);

namespace Tests\Unit\RabbitMq\Impl\Batch;

use Bunny\Message;
use Exception;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchActionAbstract;
use PHPUnit\Framework\TestCase;
use React\EventLoop\Factory;

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
     * @param array  $headers
     * @param string $content
     *
     * @return Message
     */
    private function createMessage(array $headers = [], string $content = ''): Message
    {
        return new Message('', '', FALSE, '', '', $headers, $content);
    }

    /**
     * @covers BatchActionAbstract::validateHeaders()
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
    }

    /**
     * @covers BatchActionAbstract::validateHeaders()
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
                function (): void {
                    self::fail();
                }
            )->done();

        $loop->run();
    }

}
