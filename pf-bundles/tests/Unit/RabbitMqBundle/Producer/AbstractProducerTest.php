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
use PHPUnit_Framework_MockObject_MockObject;
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
     * @return void
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
        $this->assertNull($this->producer->getSerializer());
        $serializer = $this->producer->getMeta();
        $this->assertInstanceOf(JsonSerializer::class, $serializer);
    }

    /**
     * @return void
     */
    public function testCreateMeta(): void
    {
        $publisher = $this->getPublisher($this->getDefaultBunnyManager(), '');
        $this->assertNull($publisher->createMeta());
    }

    /**
     * @return void
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
     */
    public function testPublishNoSerializer(): void
    {
        $this->expectException(BunnyException::class);
        $publisher = $this->getPublisher($this->getDefaultBunnyManager(), '', '');
        $publisher->publish('[1,2,3]');
    }

    /**
     * @param PHPUnit_Framework_MockObject_MockObject $bunnyManager
     * @param string                                  $serializerClassName
     * @param string                                  $beforeExecute
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|AbstractProducer
     */
    protected function getPublisher(
        PHPUnit_Framework_MockObject_MockObject $bunnyManager,
        $serializerClassName = JsonSerializer::class,
        $beforeExecute = 'beforeExecute'
    ): AbstractProducer
    {

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

    /**
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    protected function getDefaultBunnyManager(): PHPUnit_Framework_MockObject_MockObject
    {
        return $this->getMockBuilder(BunnyManager::class)->disableOriginalConstructor()->getMock();
    }

}
