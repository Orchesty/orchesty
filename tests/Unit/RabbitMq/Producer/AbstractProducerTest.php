<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 23.8.17
 * Time: 17:04
 */

namespace Tests\Unit\RabbitMq\Producer;

use Bunny\Channel;
use Bunny\Exception\BunnyException;
use Exception;
use Hanaboso\PipesFramework\HbPFRabbitMqBundle\ContentTypes;
use Hanaboso\PipesFramework\RabbitMq\BunnyManager;
use Hanaboso\PipesFramework\RabbitMq\Producer\AbstractProducer;
use Hanaboso\PipesFramework\RabbitMq\Serializers\JsonSerializer;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\KernelTestCaseAbstract;

/**
 * Class AbstractProducerTest
 *
 * @package Tests\Unit\RabbitMq\Base
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
        $this->assertEquals('foo', $this->producer->getExchange());
        $this->assertEquals('*.*', $this->producer->getRoutingKey());
        $this->assertFalse($this->producer->isMandatory());
        $this->assertTrue($this->producer->isImmediate());
        $this->assertEquals(JsonSerializer::class, $this->producer->getSerializerClassName());
        $this->assertEquals('beforeExecute', $this->producer->getBeforeMethod());
        $this->assertEquals(ContentTypes::APPLICATION_JSON, $this->producer->getContentType());
        $this->assertInstanceOf(BunnyManager::class, $this->producer->getManager());
    }

    /**
     * @return void
     */
    public function testGetMeta(): void
    {
        $serializer = $this->producer->getSerializer();
        $this->assertInstanceOf(JsonSerializer::class, $serializer);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testCreateMeta(): void
    {
        $publisher = $this->getPublisher($this->getDefaultBunnyManager(), '');
        $this->assertNull($publisher->createSerializer());
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testPublish(): void
    {
        $bunnyManager = $this->getMockBuilder(BunnyManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $channel = $this->getMockBuilder(Channel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $channel->expects($this->once())->method('publish')->with('[1,3,2]');

        $bunnyManager->method('getChannel')->willReturn($channel);

        $publisher = $this->getPublisher($bunnyManager, JsonSerializer::class, '');

        $publisher->publish('[1,3,2]');
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testPublishNoSerializer(): void
    {
        $this->expectException(BunnyException::class);
        $publisher = $this->getPublisher($this->getDefaultBunnyManager(), '', '');
        $publisher->publish('[1,2,3]');
    }

    /**
     * @param MockObject $bunnyManager
     * @param string     $serializerClassName
     * @param string     $beforeExecute
     *
     * @return MockObject|AbstractProducer
     * @throws Exception
     */
    protected function getPublisher(
        MockObject $bunnyManager,
        $serializerClassName = JsonSerializer::class,
        $beforeExecute = 'beforeExecute'
    ): AbstractProducer
    {
        /** @var MockObject $producer */
        $producer = $this->getMockForAbstractClass(AbstractProducer::class, [
            'foo',
            '*.*',
            FALSE,
            TRUE,
            $serializerClassName,
            $beforeExecute,
            ContentTypes::APPLICATION_JSON,
            $bunnyManager,
        ]);

        return $producer;
    }

    /**
     * @return MockObject
     */
    protected function getDefaultBunnyManager(): MockObject
    {
        return $this->getMockBuilder(BunnyManager::class)->disableOriginalConstructor()->getMock();
    }

}
