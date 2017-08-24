<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 23.8.17
 * Time: 17:04
 */

namespace Tests\Unit\RabbitMqBundle\Producer;

use Bunny\Channel;
use Bunny\Exception\BunnyException;
use Hanaboso\PipesFramework\RabbitMqBundle\BunnyManager;
use Hanaboso\PipesFramework\RabbitMqBundle\ContentTypes;
use Hanaboso\PipesFramework\RabbitMqBundle\Producer\AbstractProducer;
use Hanaboso\PipesFramework\RabbitMqBundle\Serializers\JsonSerializer;
use Tests\KernelTestCaseAbstract;

/**
 * Class AbstractProducerTest
 *
 * @package Tests\Unit\RabbitMqBundle\Producer
 */
class AbstractProducerTest extends KernelTestCaseAbstract
{

    /**
     * @var AbstractProducer
     */
    protected $producer;

    /**
     *
     */
    protected function setUp()
    {
        $this->producer = $this->getPublisher();
    }

    /**
     *
     */
    public function testParamFromConstructor()
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
     *
     */
    public function testGetMeta()
    {
        $this->assertNull($this->producer->getSerializer());
        $serializer = $this->producer->getMeta();
        $this->assertInstanceOf(JsonSerializer::class, $serializer);
    }

    /**
     *
     */
    public function testCreateMeta()
    {
        $publisher = $this->getPublisher('');
        $this->assertNull($publisher->createMeta());
    }

    /**
     *
     */
    public function testPublish()
    {
        $bunnyManager = $this->getMockBuilder(BunnyManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $channel = $this->getMockBuilder(Channel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $channel->expects($this->once())->method('publish')->with('[1,3,2]');

        $bunnyManager->method('getChannel')->willReturn($channel);

        $publisher = $this->getPublisher(JsonSerializer::class, '', $bunnyManager);

        $publisher->publish('[1,3,2]');
    }

    public function testPublishNoSerializer()
    {
        $this->expectException(BunnyException::class);
        $publisher = $this->getPublisher('', '');
        $publisher->publish('[1,2,3]');
    }

    /**
     * @param string $serializerClassName
     * @param string $beforeExecute
     * @param null   $bunnyManager
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|AbstractProducer
     */
    protected function getPublisher(
        $serializerClassName = JsonSerializer::class,
        $beforeExecute = 'beforeExecute',
        $bunnyManager = NULL
    )
    {
        if (!$bunnyManager) {
            $bunnyManager = $this->getMockBuilder(BunnyManager::class)->disableOriginalConstructor()->getMock();
        }

        return $this->getMockForAbstractClass(AbstractProducer::class, [
            'foo',
            '*.*',
            FALSE,
            TRUE,
            $serializerClassName,
            $beforeExecute,
            ContentTypes::APPLICATION_JSON,
            $bunnyManager,
        ]);
    }
}
