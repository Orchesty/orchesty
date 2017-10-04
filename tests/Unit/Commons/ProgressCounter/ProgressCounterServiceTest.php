<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 3.10.17
 * Time: 21:24
 */

namespace Tests\Unit\Commons\ProgressCounter;

use Hanaboso\PipesFramework\Commons\Enum\ProgressCounterStatusEnum;
use Hanaboso\PipesFramework\Commons\ProgressCounter\ProgressCounterService;
use Hanaboso\PipesFramework\RabbitMq\Producer\AbstractProducer;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Predis\Client;
use Tests\PrivateTrait;

/**
 * Class ProgressCounterServiceTest
 *
 * @package Tests\Unit\Commons\ProgressCounter
 */
class ProgressCounterServiceTest extends TestCase
{

    use PrivateTrait;

    /**
     * @var MockInterface|Client
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
        parent::setUp();
        $this->redis    = Mockery::mock(Client::class);
        $this->producer = $this->getMockBuilder(AbstractProducer::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @covers ProgressCounterService::setTotal()
     */
    public function testSetTotal(): void
    {
        $this->redis->shouldReceive('set')->with('aEcBuFkS12345:total', 6)->once()->andReturnUndefined();
        $this->redis->shouldReceive('get')->times(3);
        $this->producer->expects($this->once())->method('publish')->willReturn(TRUE);

        $processStatus = new ProgressCounterService($this->redis, $this->producer);
        $processStatus->setTotal('aEcBuFkS12345', 6);
    }

    /**
     * @covers ProgressCounterService::increment()
     */
    public function testIncrement(): void
    {
        $this->redis->shouldReceive('incr')->with('aEcBuFkS12345:progress')->once()->andReturnUndefined();
        $this->redis->shouldReceive('get')->times(3);
        $this->producer->expects($this->once())->method('publish')->willReturn(TRUE);

        $processStatus = new ProgressCounterService($this->redis, $this->producer);
        $processStatus->increment('aEcBuFkS12345');
    }

    /**
     * @covers ProgressCounterService::setStatus()
     */
    public function testSetStatusFailed(): void
    {
        $this->redis->shouldReceive('set')
            ->with('aEcBuFkS12345:status', ProgressCounterStatusEnum::FAILED)
            ->once()
            ->andReturnUndefined();
        $this->redis->shouldReceive('get')->times(3);
        $this->producer->expects($this->once())->method('publish')->willReturn(TRUE);

        $processStatus = new ProgressCounterService($this->redis, $this->producer);
        $processStatus->setStatus('aEcBuFkS12345', new ProgressCounterStatusEnum(ProgressCounterStatusEnum::FAILED));
    }

    /**
     * @covers ProgressCounterService::setStatus()
     */
    public function testSetStatusSuccess(): void
    {
        $this->redis->shouldReceive('set')
            ->with('aEcBuFkS12345:status', ProgressCounterStatusEnum::SUCCESS)
            ->once()
            ->andReturnUndefined();

        $this->redis->shouldReceive('del')
            ->with([
                'aEcBuFkS12345:groups',
                'aEcBuFkS12345:users',
                'aEcBuFkS12345:status',
                'aEcBuFkS12345:progress',
                'aEcBuFkS12345:total',
            ])
            ->once()
            ->andReturnUndefined();
        $this->redis->shouldReceive('get')->times(3);
        $this->producer->expects($this->once())->method('publish')->willReturn(TRUE);

        $processStatus = new ProgressCounterService($this->redis, $this->producer);
        $processStatus->setStatus('aEcBuFkS12345', new ProgressCounterStatusEnum(ProgressCounterStatusEnum::SUCCESS));
    }

    /**
     * @covers ProgressCounterService::prepareMessage()
     */
    public function testPrepareMessage(): void
    {
        $this->redis->shouldReceive('get')
            ->andReturn(5, 3, ProgressCounterStatusEnum::FAILED);

        $processStatus = new ProgressCounterService($this->redis, $this->producer);
        $message       = $this->invokeMethod($processStatus, 'prepareMessage', ['aEcBuFkS12345']);
        $this->assertEquals(
            ['total' => 5, 'progress' => 3, 'status' => 'failed', 'process_id' => 'aEcBuFkS12345'],
            $message
        );
    }

}
