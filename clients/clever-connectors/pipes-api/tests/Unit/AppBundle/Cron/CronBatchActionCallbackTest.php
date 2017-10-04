<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/3/17
 * Time: 11:46 AM
 */

namespace Tests\Unit\AppBundle\Cron;

use Bunny\Message;
use CleverConnectors\AppBundle\Model\Cron\CronBatchActionCallback;
use Exception;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use InvalidArgumentException;
use JMS\Serializer\Serializer;
use PHPUnit_Framework_MockObject_MockObject;
use React\EventLoop\Factory;
use React\Promise\Promise;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class CronBatchActionCallbackTest
 *
 * @package Tests\Unit\AppBundle\Cron
 */
class CronBatchActionCallbackTest extends KernelTestCase
{

    /**
     * @var callable
     */
    private $callback;

    /**
     * @var string
     */
    private $projectDir;

    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->projectDir = self::bootKernel()->getContainer()->getParameter('kernel.project_dir');
        $this->callback   = function (): void {
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
        return new Message(
            'consumer_tag',
            'delivery_tag',
            FALSE,
            'exchange',
            'routing_key',
            $headers,
            $content);
    }

    /**
     * @covers CronBatchActionCallback::parseBody()
     */
    public function testParseBodyError(): void
    {
        $loop = Factory::create();

        /** @var Serializer|PHPUnit_Framework_MockObject_MockObject $serializer */
        $serializer = $this->createMock(Serializer::class);
        $serializer->method('deserialize')->willThrowException(new Exception('Json error.'));
        $callback = new CronBatchActionCallback($serializer, $this->projectDir);

        $callback
            ->batchAction($this->createMessage(), $loop, $this->callback)
            ->then(NULL, function (Exception $e) use ($loop): void {
                $this->assertInstanceOf(Exception::class, $e);
                $this->assertSame('Json error.', $e->getMessage());
                $loop->stop();
            })
            ->done();

        $loop->run();
    }

    /**
     * @covers CronBatchActionCallback::getConnectorKey()
     */
    public function testConnectorKeyError(): void
    {
        $loop = Factory::create();

        /** @var Serializer|PHPUnit_Framework_MockObject_MockObject $serializer */
        $serializer = $this->createMock(Serializer::class);
        $serializer->method('deserialize')->willReturn([]);
        $callback = new CronBatchActionCallback($serializer, $this->projectDir);

        $callback
            ->batchAction($this->createMessage(), $loop, $this->callback)
            ->then(NULL, function (Exception $e) use ($loop): void {
                $this->assertInstanceOf(InvalidArgumentException::class, $e);
                $this->assertSame('Body has not system key.', $e->getMessage());
                $loop->stop();
            })
            ->done();

        $loop->run();
    }

    /**
     * @covers CronBatchActionCallback::processSystem()
     */
    public function testProcessSystemReject(): void
    {
        $loop = Factory::create();

        /** @var Serializer|PHPUnit_Framework_MockObject_MockObject $serializer */
        $serializer = $this->createMock(Serializer::class);
        $serializer->method('deserialize')->willReturn(["data" => ["param" => ""]]);
        $callback = new CronBatchActionCallback($serializer, $this->projectDir);

        $callback
            ->batchAction($this->createMessage(), $loop, $this->callback)
            ->then(NULL, function (Exception $e) use ($loop): void {
                $this->assertInstanceOf(Exception::class, $e);
                $this->assertSame('Process exited with code 1.', $e->getMessage());
                $loop->stop();
            })
            ->done();

        $loop->run();
    }

    /**
     * @covers CronBatchActionCallback::batchAction()
     */
    public function testBatchAction(): void
    {
        $loop = Factory::create();

        /** @var Serializer|PHPUnit_Framework_MockObject_MockObject $serializer */
        $serializer = $this->createMock(Serializer::class);
        $serializer->method('deserialize')->willReturn(["data" => ["param" => "test"]]);
        $callback = new CronBatchActionCallback($serializer, $this->projectDir);

        /** @var Promise $callback */
        $callback
            ->batchAction($this->createMessage(), $loop, $this->callback)
            ->then(function () use ($loop): void {
                // Test if resolve
                $this->assertTrue(TRUE);
                $loop->stop();
            }, function () use ($loop): void {
                // Test if reject
                $this->assertTrue(FALSE);
                $loop->stop();
            })
            ->done();

        $loop->run();
    }

    /**
     * @covers CronBatchActionCallback::prepareData()
     */
    public function testPrepareMessage(): void
    {
        $loop = Factory::create();

        /** @var Serializer|PHPUnit_Framework_MockObject_MockObject $serializer */
        $serializer = $this->createMock(Serializer::class);
        $serializer->method('deserialize')->willReturn(["data" => ["param" => "test"]]);
        $callback = new CronBatchActionCallback($serializer, $this->projectDir);

        $callback
            ->prepareData(['id' => '5'], 1)
            ->then(function (SuccessMessage $message) use ($loop): void {
                $this->assertSame(1, $message->getSequenceId());
                $this->assertSame('{"id":"5"}', $message->getData());
                $this->assertSame('[]', $message->getSetting());
                $loop->stop();
            })
            ->done();

        $loop->run();
    }

}