<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/3/17
 * Time: 11:46 AM
 */

namespace Tests\Unit\AppBundle\Model\CustomNode;

use CleverConnectors\AppBundle\Model\Command\AsyncCommandFactory;
use CleverConnectors\AppBundle\Model\CustomNode\UserMessageGenerator;
use Exception;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use InvalidArgumentException;
use JMS\Serializer\Serializer;
use MongoDB\Exception\RuntimeException;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use React\EventLoop\Factory;
use React\Promise\Promise;
use Throwable;

/**
 * Class UserMessageGeneratorTest
 *
 * @package Tests\Unit\AppBundle\Cron
 */
final class UserMessageGeneratorTest extends TestCase
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
     * @return ProcessDto
     */
    private function createMessage(array $headers = [], string $content = ''): ProcessDto
    {
        return (new ProcessDto())
            ->setHeaders($headers)
            ->setData($content);
    }

    /**
     * @covers UserMessageGenerator::parseBody()
     */
    public function testParseBodyError(): void
    {
        $loop = Factory::create();

        /** @var Serializer|PHPUnit_Framework_MockObject_MockObject $serializer */
        $serializer = $this->createMock(Serializer::class);
        $serializer->method('deserialize')->willThrowException(new Exception('Json error.'));
        /** @var AsyncCommandFactory|PHPUnit_Framework_MockObject_MockObject $asyncCommandFactory */
        $asyncCommandFactory = $this->createMock(AsyncCommandFactory::class);
        $callback            = new UserMessageGenerator($serializer, $asyncCommandFactory);

        $callback
            ->processBatch($this->createMessage(), $loop, $this->callback)
            ->then(NULL, function (Exception $e) use ($loop): void {
                $this->assertInstanceOf(Exception::class, $e);
                $this->assertSame('Json error.', $e->getMessage());
                $loop->stop();
            })
            ->done();

        $loop->run();
    }

    /**
     * @covers UserMessageGenerator::getSystemKey()
     */
    public function testSystemKeyError(): void
    {
        $loop = Factory::create();

        /** @var Serializer|PHPUnit_Framework_MockObject_MockObject $serializer */
        $serializer = $this->createMock(Serializer::class);
        $serializer->method('deserialize')->willReturn([]);
        /** @var AsyncCommandFactory|PHPUnit_Framework_MockObject_MockObject $asyncCommandFactory */
        $asyncCommandFactory = $this->createMock(AsyncCommandFactory::class);
        $callback            = new UserMessageGenerator($serializer, $asyncCommandFactory);

        $callback
            ->processBatch($this->createMessage(), $loop, $this->callback)
            ->then(NULL, function (Exception $e) use ($loop): void {
                $this->assertInstanceOf(InvalidArgumentException::class, $e);
                $this->assertSame('Body has not system key.', $e->getMessage());
                $loop->stop();
            })
            ->done();

        $loop->run();
    }

    /**
     * @covers UserMessageGenerator::getSystems()
     */
    public function testProcessSystemReject(): void
    {
        $loop = Factory::create();

        /** @var Serializer|PHPUnit_Framework_MockObject_MockObject $serializer */
        $serializer = $this->createMock(Serializer::class);
        $serializer->method('deserialize')->willReturn(["param" => ""]);
        /** @var AsyncCommandFactory|PHPUnit_Framework_MockObject_MockObject $asyncCommandFactory */
        $asyncCommandFactory = $this->createMock(AsyncCommandFactory::class);
        $asyncCommandFactory->method('create')->willReturn(new Promise(function ($resolve, $reject): void {
            $reject(new RuntimeException('Process exited with code 1.'));
        }));
        $callback = new UserMessageGenerator($serializer, $asyncCommandFactory);

        $callback
            ->processBatch($this->createMessage(['node_id' => '132']), $loop, $this->callback)
            ->then(NULL, function (Throwable $e) use ($loop): void {
                $this->assertInstanceOf(Exception::class, $e);
                $this->assertSame('Process exited with code 1.', $e->getMessage());
                $loop->stop();
            })
            ->done();

        $loop->run();
    }

    /**
     * @covers UserMessageGenerator::batchAction()
     */
    public function testBatchAction(): void
    {
        $loop = Factory::create();

        /** @var Serializer|PHPUnit_Framework_MockObject_MockObject $serializer */
        $serializer = $this->createMock(Serializer::class);
        $serializer->method('deserialize')->willReturn(["param" => "test"], []);
        /** @var AsyncCommandFactory|PHPUnit_Framework_MockObject_MockObject $asyncCommandFactory */
        $asyncCommandFactory = $this->createMock(AsyncCommandFactory::class);
        $callback            = new UserMessageGenerator($serializer, $asyncCommandFactory);
        $asyncCommandFactory->method('create')->willReturn(new Promise(function ($resolve): void {
            $resolve('');
        }));
        /** @var Promise $callback */
        $callback
            ->processBatch($this->createMessage(['node_id' => '123']), $loop, $this->callback)
            ->then(function () use ($loop): void {
                // Test if resolve
                $this->assertTrue(TRUE);
                $loop->stop();
            }, function ($e) use ($loop): void {
                // Test if reject
                var_dump($e);
                $this->assertTrue(FALSE);
                $loop->stop();
            })
            ->done();

        $loop->run();
    }

    /**
     * @covers UserMessageGenerator::prepareData()
     */
    public function testPrepareMessage(): void
    {
        $loop = Factory::create();

        /** @var Serializer|PHPUnit_Framework_MockObject_MockObject $serializer */
        $serializer = $this->createMock(Serializer::class);
        $serializer->method('deserialize')->willReturn(["param" => "test"]);
        /** @var AsyncCommandFactory|PHPUnit_Framework_MockObject_MockObject $asyncCommandFactory */
        $asyncCommandFactory = $this->createMock(AsyncCommandFactory::class);
        $callback            = new UserMessageGenerator($serializer, $asyncCommandFactory);

        $callback
            ->prepareData(['id' => '5', 'token' => '123', 'user' => '123'], 1)
            ->then(function (SuccessMessage $message) use ($loop): void {
                $this->assertSame(1, $message->getSequenceId());
                $this->assertSame('{"system_install":{"id":"5","token":"123","user":"123"}}', $message->getData());
                $loop->stop();
            })
            ->done();

        $loop->run();
    }

}