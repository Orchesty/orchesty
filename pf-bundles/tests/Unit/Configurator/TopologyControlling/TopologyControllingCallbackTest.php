<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: pavel.severyn
 * Date: 13.12.17
 * Time: 11:27
 */

namespace Tests\Unit\Configurator\TopologyControlling;

use Bunny\Message;
use Hanaboso\PipesFramework\Configurator\TopologyControlling\TopologyControllingCallback;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\GeneratorHandler;
use Hanaboso\PipesFramework\RabbitMq\CallbackStatus;
use Hanaboso\PipesFramework\TopologyGenerator\Exception\TopologyGeneratorException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class TopologyControllingCallbackTest
 *
 * @package Tests\Unit\Configurator\TopologyControlling
 */
class TopologyControllingCallbackTest extends TestCase
{

    /**
     * @covers       TopologyControllingCallback::handle()
     * @dataProvider handle
     *
     * @param array                           $data
     * @param int                             $expectCountStop
     * @param int                             $expectCountDelete
     * @param CallbackStatus                  $expected
     * @param TopologyGeneratorException|NULL $throwException
     */
    public function testHandle(
        array $data,
        int $expectCountStop = 0,
        $expectCountDelete = 0,
        CallbackStatus $expected,
        ?TopologyGeneratorException $throwException = NULL
    ): void
    {
        $bunnyMessage     = $this->getMockBuilder(Message::class)->disableOriginalConstructor()->getMock();
        $generatorHandler = $this->getMockBuilder(GeneratorHandler::class)
            ->disableOriginalConstructor()
            ->setMethods(['stopTopology', 'destroyTopology'])
            ->getMock();

        if ($throwException) {
            $generatorHandler->expects($this->exactly($expectCountStop))
                ->method('stopTopology')
                ->willThrowException($throwException);
        } else {
            $generatorHandler->expects($this->exactly($expectCountStop))
                ->method('stopTopology')
                ->willReturn(NULL);
        }

        if ($throwException) {
            $generatorHandler->expects($this->exactly($expectCountDelete))
                ->method('destroyTopology')
                ->willThrowException($throwException);
        } else {
            $generatorHandler->expects($this->exactly($expectCountDelete))
                ->method('destroyTopology')
                ->willReturn(TRUE);
        }

        /** @var GeneratorHandler|MockObject $generatorHandler */
        $callback = new TopologyControllingCallback($generatorHandler);

        /**
         * @var Message $bunnyMessage
         */
        $result = $callback->handle($data, $bunnyMessage);
        $this->assertEquals($expected->getStatus(), $result->getStatus());
        $this->assertInstanceOf(get_class($expected), $result);
    }

    /**
     * @return array
     */
    public function handle(): array
    {
        return [
            [['topologyId' => "123456", 'action' => 'stop'], 1, 0, new CallbackStatus(1), NULL, NULL],
            [['topologyId' => "123456", 'action' => 'delete'], 1, 1, new CallbackStatus(1), NULL, NULL],
            [['topologyId' => "123456", 'action' => 'run'], 0, 0, new CallbackStatus(2), NULL, NULL],
            [[], 0, 0, new CallbackStatus(2), NULL, NULL],
            [
                ['topologyId' => "123456", 'action' => 'delete'], 1, 0, new CallbackStatus(3),
                new TopologyGeneratorException(),
            ],
        ];
    }

}
