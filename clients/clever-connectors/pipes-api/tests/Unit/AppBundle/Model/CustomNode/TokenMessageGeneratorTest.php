<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10.10.17
 * Time: 15:51
 */

namespace Tests\Unit\AppBundle\Model\CustomNode;

use CleverConnectors\AppBundle\Model\Command\AsyncCommandFactory;
use CleverConnectors\AppBundle\Model\CustomNode\TokenMessageGenerator;
use CleverConnectors\AppBundle\Model\CustomNode\UserMessageGenerator;
use Exception;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use JMS\Serializer\Serializer;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use React\EventLoop\Factory;
use React\Promise\Promise;

/**
 * Class TokenMessageGeneratorTest
 *
 * @package Tests\Unit\AppBundle\Model\CustomNode
 */
class TokenMessageGeneratorTest extends TestCase
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
     * @covers TokenMessageGenerator::parseBody()
     * @covers TokenMessageGenerator::getExpiredSystems()
     */
    public function testParseBodyError(): void
    {
        $loop = Factory::create();

        /** @var Serializer|PHPUnit_Framework_MockObject_MockObject $serializer */
        $serializer = $this->createMock(Serializer::class);
        $serializer->method('deserialize')->willThrowException(new Exception('Json error.'));
        /** @var AsyncCommandFactory|PHPUnit_Framework_MockObject_MockObject $asyncCommandFactory */
        $asyncCommandFactory = $this->createMock(AsyncCommandFactory::class);
        $asyncCommandFactory->method('create')->willReturn(new Promise(function ($resolve): void {
            $resolve('');
        }));
        $callback = new TokenMessageGenerator($serializer, $asyncCommandFactory);

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
     * @covers UserMessageGenerator::prepareData()
     */
    public function testPrepareMessage(): void
    {
        $loop = Factory::create();

        /** @var Serializer|PHPUnit_Framework_MockObject_MockObject $serializer */
        $serializer = $this->createMock(Serializer::class);
        $serializer->method('deserialize')->willReturn(["data" => ["param" => "test"]]);
        /** @var AsyncCommandFactory|PHPUnit_Framework_MockObject_MockObject $asyncCommandFactory */
        $asyncCommandFactory = $this->createMock(AsyncCommandFactory::class);
        $callback            = new TokenMessageGenerator($serializer, $asyncCommandFactory);

        $callback
            ->prepareData(['id' => '5', 'token' => '123', 'user' => '123'], 1)
            ->then(function (SuccessMessage $message) use ($loop): void {
                $this->assertSame(1, $message->getSequenceId());
                $this->assertSame('{"id":"5","token":"123","user":"123"}', $message->getData());
                $this->assertSame('[]', $message->getSetting());
                $loop->stop();
            })
            ->done();

        $loop->run();
    }

}