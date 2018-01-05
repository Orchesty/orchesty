<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 25.9.17
 * Time: 11:10
 */

namespace Hanaboso\PipesFramework\RabbitMq\Consumer;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class ConsumerAbstract
 *
 * @package Hanaboso\PipesFramework\RabbitMqBundle\Consumer
 */
abstract class ConsumerAbstract
{

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string
     */
    private $exchange;

    /**
     * @var string
     */
    private $routingKey;

    /**
     * @var string
     */
    private $queue;

    /**
     * @var string
     */
    private $consumerTag;

    /**
     * @var bool
     */
    private $noLocal;

    /**
     * @var bool
     */
    private $noAck;

    /**
     * @var bool
     */
    private $exclusive;

    /**
     * @var bool
     */
    private $nowait;

    /**
     * @var array
     */
    private $arguments;

    /**
     * @var int
     */
    private $prefetchCount;

    /**
     * @var int
     */
    private $prefetchSize;

    /**
     * @var string|null
     */
    private $serializer;

    /**
     * AbstractConsumer constructor.
     *
     * @param string      $exchange
     * @param string      $routingKey
     * @param string      $queue
     * @param string      $consumerTag
     * @param bool        $noLocal
     * @param bool        $noAck
     * @param bool        $exclusive
     * @param bool        $nowait
     * @param array       $arguments
     * @param int         $prefetchCount
     * @param int         $prefetchSize
     * @param string|null $serializer
     */
    public function __construct(
        string $exchange = '',
        string $routingKey = '',
        string $queue = '',
        string $consumerTag = '',
        bool $noLocal = FALSE,
        bool $noAck = FALSE,
        bool $exclusive = FALSE,
        bool $nowait = FALSE,
        array $arguments = [],
        int $prefetchCount = 1,
        int $prefetchSize = 0,
        ?string $serializer = NULL
    )
    {
        $this->exchange      = $exchange;
        $this->routingKey    = $routingKey;
        $this->queue         = $queue;
        $this->consumerTag   = $consumerTag;
        $this->noLocal       = $noLocal;
        $this->noAck         = $noAck;
        $this->exclusive     = $exclusive;
        $this->nowait        = $nowait;
        $this->arguments     = $arguments;
        $this->prefetchCount = $prefetchCount;
        $this->prefetchSize  = $prefetchSize;
        $this->serializer    = $serializer;

        $this->logger = new NullLogger();
    }

    /**
     * @return string
     */
    public function getExchange(): string
    {
        return $this->exchange;
    }

    /**
     * @return string
     */
    public function getRoutingKey(): string
    {
        return $this->routingKey;
    }

    /**
     * @return string
     */
    public function getQueue(): string
    {
        return $this->queue;
    }

    /**
     * @param string $queue
     */
    public function setQueue(string $queue): void
    {
        $this->queue = $queue;
    }

    /**
     * @return string
     */
    public function getConsumerTag(): string
    {
        return $this->consumerTag;
    }

    /**
     * @return bool
     */
    public function isNoLocal(): bool
    {
        return $this->noLocal;
    }

    /**
     * @return bool
     */
    public function isNoAck(): bool
    {
        return $this->noAck;
    }

    /**
     * @return bool
     */
    public function isExclusive(): bool
    {
        return $this->exclusive;
    }

    /**
     * @return bool
     */
    public function isNowait(): bool
    {
        return $this->nowait;
    }

    /**
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @return int
     */
    public function getPrefetchCount(): int
    {
        return $this->prefetchCount;
    }

    /**
     * @return int
     */
    public function getPrefetchSize(): int
    {
        return $this->prefetchSize;
    }

    /**
     * @return string|null
     */
    public function getSerializer(): ?string
    {
        return $this->serializer;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

}