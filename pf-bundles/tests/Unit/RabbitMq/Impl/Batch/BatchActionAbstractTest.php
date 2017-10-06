<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 6.10.17
 * Time: 19:27
 */

namespace Tests\Unit\RabbitMq\Impl\Batch;

use Bunny\Message;
use Exception;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\BatchActionAbstract;
use PHPUnit\Framework\TestCase;
use React\EventLoop\Factory;

/**
 * Class BatchActionAbstractTest
 *
 * @package Tests\Unit\RabbitMq\Impl\Batch
 */
class BatchActionAbstractTest extends TestCase
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
     */
    public function testValidateHeaders(): void
    {
        $loop = Factory::create();

        /** @var BatchActionAbstract $batchAction */
        $batchAction = $this->getMockForAbstractClass(BatchActionAbstract::class);

        $batchAction
            ->batchAction($this->createMessage(), $loop, $this->callback)
            ->then(NULL, function (Exception $e) use ($loop): void {
                $this->assertInstanceOf(Exception::class, $e);
                $this->assertSame('Missing "node_name" in the message header.', $e->getMessage());
                $loop->stop();
            })->done();

        $loop->run();
    }

    /**
     * @covers BatchActionAbstract::validateHeaders()
     */
    public function testBatchAction(): void
    {
        $loop = Factory::create();

        /** @var BatchActionAbstract $batchAction */
        $batchAction = $this->getMockForAbstractClass(BatchActionAbstract::class);

        $batchAction
            ->batchAction($this->createMessage(['node_name' => 'abc']), $loop, $this->callback)
            ->then(function () use ($loop): void {
                $this->assertTrue(TRUE);
                $loop->stop();
            }, function (): void {
                $this->assertTrue(FALSE);
            })->done();

        $loop->run();
    }

}