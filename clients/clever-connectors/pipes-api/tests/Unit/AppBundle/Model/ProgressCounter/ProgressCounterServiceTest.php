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
use CleverConnectors\AppBundle\Model\ProgressCounter\Publisher\IProgressPublisher;
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
     * @var PHPUnit_Framework_MockObject_MockObject|IProgressPublisher
     */
    protected $progressPublisher;

    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->redis             = $this->getMockBuilder(Client::class)->setMethods([
            'del', 'hmset', 'hset', 'hgetall', 'hincrby',
        ])->getMock();
        $this->progressPublisher = $this->createMock(IProgressPublisher::class);
    }

    /**
     * @covers ProgressCounterService::setTotal()
     */
    public function testSetTotal(): void
    {
        $this->redis->method('hset')->with('aEcBuFkS12345:progress_counter', 'total', 6)->willReturn(NULL);
        $this->redis->expects($this->exactly(1))->method('hgetall');
        $this->progressPublisher->expects($this->once())->method('publish')->willReturn(TRUE);

        $processStatus = new ProgressCounterService($this->redis, $this->progressPublisher);
        $processStatus->setTotal('aEcBuFkS12345', 6);
    }

    /**
     * @covers ProgressCounterService::increment()
     */
    public function testIncrement(): void
    {
        $this->redis->expects($this->once())->method('hincrby')->with('aEcBuFkS12345:progress_counter', 'progress')
            ->willReturn(NULL);
        $this->redis->expects($this->exactly(1))->method('hgetall');
        $this->progressPublisher->expects($this->once())->method('publish')->willReturn(TRUE);

        $processStatus = new ProgressCounterService($this->redis, $this->progressPublisher);
        $processStatus->increment('aEcBuFkS12345');
    }

    /**
     * @covers ProgressCounterService::setStatus()
     */
    public function testSetStatusFailed(): void
    {
        $this->redis
            ->expects($this->once())
            ->method('hset')
            ->with('aEcBuFkS12345:progress_counter', 'status', ProgressCounterStatusEnum::FAILED)
            ->willReturn(NULL);
        $this->redis->expects($this->exactly(1))->method('hgetall');
        $this->progressPublisher->expects($this->once())->method('publish')->willReturn(TRUE);

        $processStatus = new ProgressCounterService($this->redis, $this->progressPublisher);
        $processStatus->setStatus('aEcBuFkS12345', new ProgressCounterStatusEnum(ProgressCounterStatusEnum::FAILED));
    }

    /**
     * @covers ProgressCounterService::setStatus()
     */
    public function testSetStatusSuccess(): void
    {
        $this->redis
            ->expects($this->once())
            ->method('hset')
            ->with('aEcBuFkS12345:progress_counter', 'status', ProgressCounterStatusEnum::SUCCESS)
            ->willReturn(NULL);
        $this->redis
            ->expects($this->once())
            ->method('del')
            ->with([
                'aEcBuFkS12345:progress_counter',
            ])
            ->willReturn(NULL);
        $this->redis->expects($this->exactly(1))->method('hgetall');
        $this->progressPublisher->expects($this->once())->method('publish')->willReturn(TRUE);

        $processStatus = new ProgressCounterService($this->redis, $this->progressPublisher);
        $processStatus->setStatus('aEcBuFkS12345', new ProgressCounterStatusEnum(ProgressCounterStatusEnum::SUCCESS));
    }

    /**
     * @covers ProgressCounterService::prepareMessage()
     */
    public function testPrepareMessage(): void
    {
        $this->redis
            ->expects($this->at(0))
            ->method('hgetAll')
            ->willReturn([
                'event'      => 'sync_event',
                'groups'     => json_encode(['user_id', 'admins']),
                'process_id' => 'aEcBuFkS12345',
                'total'      => 5,
                'progress'   => 3,
                'status'     => 'failed',
                'metadata'   => json_encode(['system_key' => 'system_key']),
            ]);
        $processStatus = new ProgressCounterService($this->redis, $this->progressPublisher);
        $message       = $processStatus->prepareMessage('aEcBuFkS12345');
        $this->assertEquals(
            [
                'event'   => 'sync_event',
                'groups'  => ['user_id', 'admins'],
                'content' => [
                    'process_id' => 'aEcBuFkS12345',
                    'total'      => 5,
                    'progress'   => 3,
                    'status'     => 'failed',
                    'metadata'   => [
                        'system_key' => 'system_key',
                    ],
                ],
            ],
            $message
        );
    }

}
