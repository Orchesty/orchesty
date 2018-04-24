<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 3.10.17
 * Time: 15:29
 */

namespace CleverConnectors\AppBundle\Model\ProgressCounter;

use CleverConnectors\AppBundle\Enum\ProgressCounterStatusEnum;
use CleverConnectors\AppBundle\Model\ProgressCounter\Publisher\IProgressPublisher;
use Hanaboso\CommonsBundle\Exception\EnumException;
use Predis\Client;

/**
 * Class ProgressCounterAbstract
 *
 * @package CleverConnectors\AppBundle\Model\ProgressCounter
 */
abstract class ProgressCounterAbstract implements ProgressCounterInterface
{

    use ProgressCounterTrait;

    public const PROGRESS_COUNTER          = 'progress_counter';
    public const PROGRESS_COUNTER_USERS    = 'users';
    public const PROGRESS_COUNTER_GROUPS   = 'groups';
    public const PROGRESS_COUNTER_TOTAL    = 'total';
    public const PROGRESS_COUNTER_PROGRESS = 'progress';
    public const PROGRESS_COUNTER_STATUS   = 'status';
    public const PROGRESS_COUNTER_EVENT    = 'event';
    public const PROGRESS_COUNTER_METADATA = 'metadata';

    /**
     * @var Client
     */
    protected $redis;

    /**
     * @var IProgressPublisher
     */
    private $progressPublisher;

    /**
     * ProgressCounterService constructor.
     *
     * @param Client             $redis
     * @param IProgressPublisher $progressPublisher
     */
    public function __construct(Client $redis, IProgressPublisher $progressPublisher)
    {
        $this->redis             = $redis;
        $this->progressPublisher = $progressPublisher;
    }

    /**
     * @param string $processId
     *
     * @return string
     */
    private function createKey(string $processId): string
    {
        return $this->getKey($processId, self::PROGRESS_COUNTER);
    }

    /**
     * @param string   $processId
     * @param string   $eventName
     * @param array    $groups
     * @param int|NULL $total
     * @param array    $metadata
     */
    public function start(
        string $processId,
        string $eventName,
        array $groups = [],
        ?int $total = NULL,
        array $metadata = []
    ): void
    {
        if ($total === NULL) {
            $total = 0;
        }

        $this->redis->hmset($this->createKey($processId), [
            self::PROGRESS_COUNTER_EVENT    => $eventName,
            self::PROGRESS_COUNTER_GROUPS   => json_encode($groups),
            self::PROGRESS_COUNTER_TOTAL    => $total,
            self::PROGRESS_COUNTER_PROGRESS => 0,
            self::PROGRESS_COUNTER_STATUS   => ProgressCounterStatusEnum::IN_PROGRESS,
            self::PROGRESS_COUNTER_METADATA => json_encode($metadata),
        ]);

        $this->progressPublisher->publish($this->prepareMessage($processId));
    }

    /**
     * @param string $processId
     * @param int    $total
     */
    public function setTotal(string $processId, int $total): void
    {
        $this->redis->hset($this->createKey($processId), self::PROGRESS_COUNTER_TOTAL, $total);

        $this->progressPublisher->publish($this->prepareMessage($processId));
    }

    /**
     * @param string $processId
     */
    public function increment(string $processId): void
    {
        $this->redis->hincrby($this->createKey($processId), self::PROGRESS_COUNTER_PROGRESS, 1);

        $this->progressPublisher->publish($this->prepareMessage($processId));
    }

    /**
     * @param string $processId
     * @param string $status
     *
     * @throws EnumException
     */
    public function setStatus(string $processId, string $status): void
    {
        $this->redis->hset($this->createKey($processId), self::PROGRESS_COUNTER_STATUS,
            ProgressCounterStatusEnum::isValid($status));

        $this->progressPublisher->publish($this->prepareMessage($processId));

        if ($status == ProgressCounterStatusEnum::SUCCESS) {
            $this->garbageData($processId);
        }
    }

    /**
     * @param string $processId
     *
     * @return array
     */
    public function prepareMessage(string $processId): array
    {
        $data = $this->getData($processId);

        return [
            'event'   => $data[self::PROGRESS_COUNTER_EVENT] ?? '',
            'groups'  => json_decode($data[self::PROGRESS_COUNTER_GROUPS] ?? '', TRUE),
            'content' => [
                'process_id' => $processId,
                'total'      => $data[self::PROGRESS_COUNTER_TOTAL] ?? '',
                'progress'   => $data[self::PROGRESS_COUNTER_PROGRESS] ?? '',
                'status'     => $data[self::PROGRESS_COUNTER_STATUS] ?? '',
                'metadata'   => json_decode($data[self::PROGRESS_COUNTER_METADATA] ?? '', TRUE),
            ],
        ];
    }

    /**
     * @param string $processId
     *
     * @return array
     */
    private function getData(string $processId): array
    {
        $data = $this->redis->hgetall($this->createKey($processId));

        if (!is_array($data)) {
            return [];
        }

        return $data;
    }

    /**
     * @param string $processId
     */
    protected function garbageData(string $processId): void
    {
        $this->redis->del([$this->createKey($processId)]);
    }

}
