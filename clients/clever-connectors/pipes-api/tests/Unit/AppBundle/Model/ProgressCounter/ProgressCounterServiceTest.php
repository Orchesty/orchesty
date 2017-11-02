<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 3.10.17
 * Time: 21:24
 */

namespace Tests\Unit\AppBundle\Model\ProgressCounter;

use CleverConnectors\AppBundle\Enum\ProgressCounterStatusEnum;
use CleverConnectors\AppBundle\Model\ProgressCounter\ProgressCounterService;
use Hanaboso\PipesFramework\RabbitMq\Producer\AbstractProducer;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Predis\Client;

/**
 * Class ProgressCounterServiceTest
 *
 * @package Tests\Unit\Commons\ProgressCounter
 */
class ProgressCounterServiceTest extends TestCase
{

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|Client
     */
    protected $redis;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|AbstractProducer
     */
    protected $producer;

    /**
     *
     */
    public function setUp(): void
    {
        $this->markTestSkipped();
        parent::setUp();
        $this->redis    = $this->getMockBuilder(Client::class)->setMethods([
            'set', 'get', 'incr', 'del', 'hmset', 'hgetall',
        ])->getMock();
        $this->producer = $this->getMockBuilder(AbstractProducer::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @covers ProgressCounterService::setTotal()
     */
    public function testSetTotal(): void
    {
        $this->redis->method('set')->with('aEcBuFkS12345:total', 6)->willReturn(NULL);
        $this->redis->expects($this->exactly(4))->method('get');
        $this->producer->expects($this->once())->method('publish')->willReturn(TRUE);

        //        $processStatus = new ProgressCounterService($this->redis, $this->producer);
        //        $processStatus->setTotal('aEcBuFkS12345', 6);
    }

    /**
     * @covers ProgressCounterService::increment()
     */
    public function testIncrement(): void
    {
        $this->redis->expects($this->once())->method('incr')->with('aEcBuFkS12345:progress')->willReturn(NULL);
        $this->redis->expects($this->exactly(4))->method('get');
        $this->producer->expects($this->once())->method('publish')->willReturn(TRUE);

        //        $processStatus = new ProgressCounterService($this->redis, $this->producer);
        //        $processStatus->increment('aEcBuFkS12345');
    }

    /**
     * @covers ProgressCounterService::setStatus()
     */
    public function testSetStatusFailed(): void
    {
        $this->redis
            ->expects($this->once())
            ->method('set')
            ->with('aEcBuFkS12345:status', ProgressCounterStatusEnum::FAILED)
            ->willReturn(NULL);
        $this->redis->expects($this->exactly(4))->method('get');
        $this->producer->expects($this->once())->method('publish')->willReturn(TRUE);

        //        $processStatus = new ProgressCounterService($this->redis, $this->producer);
        //        $processStatus->setStatus('aEcBuFkS12345', new ProgressCounterStatusEnum(ProgressCounterStatusEnum::FAILED));
    }

    /**
     * @covers ProgressCounterService::setStatus()
     */
    public function testSetStatusSuccess(): void
    {
        $this->redis
            ->expects($this->once())
            ->method('set')
            ->with('aEcBuFkS12345:status', ProgressCounterStatusEnum::SUCCESS)
            ->willReturn(NULL);
        $this->redis
            ->expects($this->once())
            ->method('del')
            ->with([
                'aEcBuFkS12345:groups',
                'aEcBuFkS12345:users',
                'aEcBuFkS12345:status',
                'aEcBuFkS12345:progress',
                'aEcBuFkS12345:total',
                'aEcBuFkS12345:event',
                'aEcBuFkS12345:metadata',
            ])
            ->willReturn(NULL);
        $this->redis->expects($this->exactly(4))->method('get');
        $this->producer->expects($this->once())->method('publish')->willReturn(TRUE);

        //        $processStatus = new ProgressCounterService($this->redis, $this->producer);
        //        $processStatus->setStatus('aEcBuFkS12345', new ProgressCounterStatusEnum(ProgressCounterStatusEnum::SUCCESS));
    }

    /**
     * @covers ProgressCounterService::prepareMessage()
     */
    public function testPrepareMessage(): void
    {
        $this->redis
            ->expects($this->at(0))
            ->method('get')
            ->willReturn('sync_event');
        $this->redis
            ->expects($this->at(1))
            ->method('hgetall')
            ->willReturn(['user_id', 'admins']);
        $this->redis
            ->expects($this->at(2))
            ->method('get')
            ->willReturn(5);
        $this->redis
            ->expects($this->at(3))
            ->method('get')
            ->willReturn(3);
        $this->redis
            ->expects($this->at(4))
            ->method('get')
            ->willReturn(ProgressCounterStatusEnum::FAILED);
        $this->redis
            ->expects($this->at(5))
            ->method('hgetAll')
            ->willReturn(['system_key' => 'system_key']);
        //        $processStatus = new ProgressCounterService($this->redis, $this->producer);
        //        $message       = $processStatus->prepareMessage('aEcBuFkS12345');
        //        $this->assertEquals(
        //            [
        //                'event'   => 'sync_event',
        //                'groups'  => ['user_id', 'admins'],
        //                'content' => [
        //                    'process_id' => 'aEcBuFkS12345',
        //                    'total'      => 5,
        //                    'progress'   => 3,
        //                    'status'     => 'failed',
        //                    'metadata'   => [
        //                        'system_key' => 'system_key',
        //                    ],
        //                ],
        //            ],
        //            $message
        //        );
    }

}
