<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 22.8.17
 * Time: 12:57
 */

namespace Hanaboso\PipesFramework\RabbitMqBundle\Consumer;

use Bunny\Channel;
use Bunny\Client;
use Bunny\Message;
use Hanaboso\PipesFramework\RabbitMqBundle\Serializers\IMessageSerializer;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class AbstractConsumer
 *
 * @package RabbitMqBundle\Consumer
 */
abstract class AbstractConsumer implements LoggerAwareInterface
{

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string|null
     */
    private $exchange;
    /**
     * @var string
     */
    private $routingKey;
    /**
     * @var string|null
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
     * @var int|null
     */
    private $prefetchCount;
    /**
     * @var int|null
     */
    private $prefetchSize;
    /**
     * @var string|null
     */
    private $serializer;
    /**
     * @var string|null
     */
    private $setUpMethod;
    /**
     * @var string|null
     */
    private $tickMethod;
    /**
     * @var int|null
     */
    private $tickSeconds;
    /**
     * @var int|null
     */
    private $maxMessages;
    /**
     * @var int|null
     */
    private $maxSeconds;

    /**
     * AbstractConsumer constructor.
     *
     * @param null|string             $exchange
     * @param string                  $routingKey
     * @param null|string             $queue
     * @param string                  $consumerTag
     * @param bool                    $noLocal
     * @param bool                    $noAck
     * @param bool                    $exclusive
     * @param bool                    $nowait
     * @param array                   $arguments
     * @param int|null                $prefetchCount
     * @param int|null                $prefetchSize
     * @param IMessageSerializer|null $serializer
     * @param null|string             $setUpMethod
     * @param null|string             $tickMethod
     * @param int|null                $tickSeconds
     * @param int|null                $maxMessages
     * @param int|null                $maxSeconds
     */
    public function __construct(
        ?string $exchange = NULL,
        string $routingKey = '',
        ?string $queue = NULL,
        string $consumerTag = '',
        ?bool $noLocal = FALSE,
        ?bool $noAck = FALSE,
        ?bool $exclusive = FALSE,
        ?bool $nowait = FALSE,
        array $arguments = [],
        ?int $prefetchCount = NULL,
        ?int $prefetchSize = NULL,
        ?string $serializer = NULL,
        ?string $setUpMethod = NULL,
        ?string $tickMethod = NULL,
        ?int $tickSeconds = NULL,
        ?int $maxMessages = NULL,
        ?int $maxSeconds = NULL
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
        $this->setUpMethod   = $setUpMethod;
        $this->tickMethod    = $tickMethod;
        $this->tickSeconds   = $tickSeconds;
        $this->maxMessages   = $maxMessages;
        $this->maxSeconds    = $maxSeconds;

        $this->logger = new NullLogger();
    }

    /**
     * @param mixed   $data
     * @param Message $message
     * @param Channel $channel
     * @param Client  $client
     */
    abstract function handleMessage($data, Message $message, Channel $channel, Client $client): void;

    /**
     * @return string|null
     */
    public function getExchange(): ?string
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
     * @return string|null
     */
    public function getQueue(): ?string
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
     * @return int|null
     */
    public function getPrefetchCount(): ?int
    {
        return $this->prefetchCount;
    }

    /**
     * @return int|null
     */
    public function getPrefetchSize(): ?int
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
     * @return string|null
     */
    public function getSetUpMethod(): ?string
    {
        return $this->setUpMethod;
    }

    /**
     * @return string|null
     */
    public function getTickMethod(): ?string
    {
        return $this->tickMethod;
    }

    /**
     * @return int|null
     */
    public function getTickSeconds(): ?int
    {
        return $this->tickSeconds;
    }

    /**
     * @return int|null
     */
    public function getMaxMessages(): ?int
    {
        return $this->maxMessages;
    }

    /**
     * @return int|null
     */
    public function getMaxSeconds(): ?int
    {
        return $this->maxSeconds;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

}
