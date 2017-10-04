<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 3.10.17
 * Time: 15:29
 */

namespace Hanaboso\PipesFramework\Commons\ProgressCounter;

use Hanaboso\PipesFramework\Commons\Enum\ProgressCounterStatusEnum;
use Hanaboso\PipesFramework\RabbitMq\Producer\AbstractProducer;
use Predis\Client;

/**
 * Class ProgressCounterAbstract
 *
 * @package Hanaboso\PipesFramework\Commons\ProgressCounter
 */
abstract class ProgressCounterAbstract implements ProgressCounterInterface
{

    use ProgressCounterTrait;

    /**
     * @var
     */
    //protected $stream;

    /**
     * @var Client
     */
    protected $redis;

    /**
     * @var AbstractProducer
     */
    protected $producer;

    /**
     * ProgressCounterService constructor.
     *
     * @param Client           $redis
     * @param AbstractProducer $producer
     */
    public function __construct(Client $redis, AbstractProducer $producer)
    {
        $this->redis    = $redis;
        $this->producer = $producer;
    }

    /**
     * @var string
     */
    public const PROGRESS_COUNTER_USERS = 'users';

    /**
     * @var string
     */
    public const PROGRESS_COUNTER_GROUPS = 'groups';

    /**
     * @var string
     */
    public const PROGRESS_COUNTER_TOTAL = 'total';

    /**
     * @var string
     */
    public const PROGRESS_COUNTER_PROGRESS = 'progress';

    /**
     * @var string
     */
    public const PROGRESS_COUNTER_STATUS = 'status';

    /**
     * @param string   $processId
     * @param array    $users
     * @param array    $groups
     * @param int|NULL $total
     */
    public function start(string $processId, array $users = [], array $groups = [], ?int $total = NULL): void
    {
        if (count($users)) {
            $this->redis->sadd($this->getKey($processId, self::PROGRESS_COUNTER_USERS), $users);
        }
        if (count($groups)) {
            $this->redis->sadd($this->getKey($processId, self::PROGRESS_COUNTER_GROUPS), $groups);
        }
        if ($total) {
            $this->redis->set($this->getKey($processId, self::PROGRESS_COUNTER_TOTAL), $total);
        }

        $this->producer->publish($this->prepareMessage($processId));
    }

    /**
     * @param string $processId
     * @param int    $total
     */
    public function setTotal(string $processId, int $total): void
    {
        $this->redis->set($this->getKey($processId, self::PROGRESS_COUNTER_TOTAL), $total);

        $this->producer->publish($this->prepareMessage($processId));
    }

    /**
     * @param string $processId
     */
    public function increment(string $processId): void
    {
        $this->redis->incr(self::getKey($processId, self::PROGRESS_COUNTER_PROGRESS));

        $this->producer->publish($this->prepareMessage($processId));
    }

    /**
     * @param string                    $processId
     * @param ProgressCounterStatusEnum $status
     */
    public function setStatus(string $processId, ProgressCounterStatusEnum $status): void
    {
        $this->redis->set(self::getKey($processId, self::PROGRESS_COUNTER_STATUS), $status->getValue());

        $this->producer->publish($this->prepareMessage($processId));

        if ($status->getValue() == ProgressCounterStatusEnum::SUCCESS) {
            $this->garbageData($processId);
        }
    }

    /**
     * @param string $processId
     *
     * @return array
     */
    protected function prepareMessage(string $processId): array
    {
        return [
            'process_id' => $processId,
            'total'      => $this->redis->get(self::getKey($processId, self::PROGRESS_COUNTER_TOTAL)),
            'progress'   => $this->redis->get(self::getKey($processId, self::PROGRESS_COUNTER_PROGRESS)),
            'status'     => $this->redis->get(self::getKey($processId, self::PROGRESS_COUNTER_STATUS)),
        ];
    }

    /**
     * @param string $processId
     */
    protected function garbageData(string $processId): void
    {
        $this->redis->del([
            self::getKey($processId, self::PROGRESS_COUNTER_GROUPS),
            self::getKey($processId, self::PROGRESS_COUNTER_USERS),
            self::getKey($processId, self::PROGRESS_COUNTER_STATUS),
            self::getKey($processId, self::PROGRESS_COUNTER_PROGRESS),
            self::getKey($processId, self::PROGRESS_COUNTER_TOTAL),
        ]);
    }

}
