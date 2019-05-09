<?php declare(strict_types=1);

namespace Tests\Unit\RabbitMq\Producer;

use Bunny\Channel;
use Exception;
use Hanaboso\PipesFramework\RabbitMq\BunnyManager;
use Hanaboso\PipesFramework\RabbitMq\ContentTypes;
use Hanaboso\PipesFramework\RabbitMq\Producer\AbstractProducer;
use JsonException;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\KernelTestCaseAbstract;

/**
 * Class AbstractProducerTest
 *
 * @package Tests\Unit\RabbitMq\Producer
 */
final class AbstractProducerTest extends KernelTestCaseAbstract
{

    /**
     * @var AbstractProducer
     */
    protected $producer;

    /**
     * @return void
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->producer = $this->getPublisher($this->getDefaultBunnyManager());
    }

    /**
     * @return void
     */
    public function testParamFromConstructor(): void
    {
        self::assertEquals('foo', $this->producer->getExchange());
        self::assertEquals('*.*', $this->producer->getRoutingKey());
        self::assertFalse($this->producer->isMandatory());
        self::assertTrue($this->producer->isImmediate());
        self::assertEquals('beforeExecute', $this->producer->getBeforeMethod());
        self::assertEquals(ContentTypes::APPLICATION_JSON, $this->producer->getContentType());
        self::assertInstanceOf(BunnyManager::class, $this->producer->getManager());
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testPublish(): void
    {
        $bunnyManager = self::getMockBuilder(BunnyManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $channel = self::getMockBuilder(Channel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $channel->expects($this->once())->method('publish')->with('[1,3,2]');

        $bunnyManager->method('getChannel')->willReturn($channel);

        $publisher = $this->getPublisher($bunnyManager, '');

        $publisher->publish('[1,3,2]');
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testPublishNoSerializer(): void
    {
        self::expectException(JsonException::class);
        $publisher = $this->getPublisher($this->getDefaultBunnyManager(), '');
        $publisher->publish('This is not JSON');
    }

    /**
     * @param MockObject $bunnyManager
     * @param string     $beforeExecute
     *
     * @return MockObject|AbstractProducer
     * @throws Exception
     */
    protected function getPublisher(
        MockObject $bunnyManager,
        $beforeExecute = 'beforeExecute'
    ): AbstractProducer
    {
        /** @var MockObject|AbstractProducer $producer */
        $producer = $this->getMockForAbstractClass(AbstractProducer::class, [
            'foo',
            '*.*',
            FALSE,
            TRUE,
            $beforeExecute,
            ContentTypes::APPLICATION_JSON,
            $bunnyManager,
        ]);

        return $producer;
    }

    /**
     * @return MockObject
     * @throws Exception
     */
    protected function getDefaultBunnyManager(): MockObject
    {
        return self::getMockBuilder(BunnyManager::class)->disableOriginalConstructor()->getMock();
    }

}
