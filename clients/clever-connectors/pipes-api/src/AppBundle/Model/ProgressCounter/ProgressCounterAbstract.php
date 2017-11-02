<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 3.10.17
 * Time: 15:29
 */

namespace CleverConnectors\AppBundle\Model\ProgressCounter;

use Bunny\Client as BunnyClient;
use CleverConnectors\AppBundle\Enum\ProgressCounterStatusEnum;
use Hanaboso\PipesFramework\RabbitMq\BunnyManager;
use Hanaboso\PipesFramework\RabbitMq\Producer\AbstractProducer;
use Predis\Client;

/**
 * Class ProgressCounterAbstract
 *
 * @package CleverConnectors\AppBundle\Model\ProgressCounter
 */
abstract class ProgressCounterAbstract implements ProgressCounterInterface
{

    use ProgressCounterTrait;

    /**
     * @var Client
     */
    protected $redis;

    /**
     * @var AbstractProducer
     */
    protected $producer;
    /**
     * @var BunnyManager
     */
    private $bunnyManager;

    /**
     * ProgressCounterService constructor.
     *
     * @param Client           $redis
     * @param AbstractProducer $producer
     * @param BunnyManager     $bunnyManager
     */
    public function __construct(Client $redis, AbstractProducer $producer, BunnyManager $bunnyManager)
    {
        $this->redis        = $redis;
        $this->producer     = $producer;
        $this->bunnyManager = $bunnyManager;
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
     * @var string
     */
    public const PROGRESS_COUNTER_EVENT = 'event';

    /**
     * @var string
     */
    public const PROGRESS_COUNTER_METADATA = 'metadata';

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

        $this->redis->set($this->getKey($processId, self::PROGRESS_COUNTER_EVENT), $eventName);
        $this->redis->hmset($this->getKey($processId, self::PROGRESS_COUNTER_GROUPS), $groups);
        $this->redis->set($this->getKey($processId, self::PROGRESS_COUNTER_TOTAL), $total);
        $this->redis->set($this->getKey($processId, self::PROGRESS_COUNTER_PROGRESS), 0);
        $this->redis->set(
            $this->getKey($processId, self::PROGRESS_COUNTER_STATUS),
            ProgressCounterStatusEnum::IN_PROGRESS
        );
        $this->redis->hmset($this->getKey($processId, self::PROGRESS_COUNTER_METADATA), $metadata);

        $this->producer->publish($this->prepareMessage($processId));
    }

    /**
     * @param string $processId
     * @param int    $total
     */
    public function setTotal(string $processId, int $total): void
    {
        $this->redis->set($this->getKey($processId, self::PROGRESS_COUNTER_TOTAL), $total);

        $client = new BunnyClient($this->bunnyManager->getConfig());
        $client->connect();
        $client->channel()->publish(
            json_encode($this->prepareMessage($processId)),
            ['content-type' => 'application/json'],
            '',
            'pipes.stream'
        );
        $client->disconnect();
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
    public function prepareMessage(string $processId): array
    {
        return [
            'event'   => $this->redis->get(self::getKey($processId, self::PROGRESS_COUNTER_EVENT)),
            'groups'  => $this->getGroups($processId),
            'content' => [
                'process_id' => $processId,
                'total'      => $this->redis->get(self::getKey($processId, self::PROGRESS_COUNTER_TOTAL)),
                'progress'   => $this->redis->get(self::getKey($processId, self::PROGRESS_COUNTER_PROGRESS)),
                'status'     => $this->redis->get(self::getKey($processId, self::PROGRESS_COUNTER_STATUS)),
                'metadata'   => $this->getMetaData($processId),
            ],
        ];
    }

    /**
     * @param string $processId
     *
     * @return array
     */
    private function getGroups(string $processId): array
    {
        $groups = $this->redis->hgetall(self::getKey($processId, self::PROGRESS_COUNTER_GROUPS));

        if (!is_array($groups)) {
            return [];
        }

        return $groups;
    }

    /**
     * @param string $processId
     *
     * @return array
     */
    private function getMetaData(string $processId): array
    {
        $metadata = $this->redis->hgetall(self::getKey($processId, self::PROGRESS_COUNTER_METADATA));

        if (!is_array($metadata)) {
            return [];
        }

        return $metadata;
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
            self::getKey($processId, self::PROGRESS_COUNTER_EVENT),
            self::getKey($processId, self::PROGRESS_COUNTER_METADATA),
        ]);
    }

}
