<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 22.8.17
 * Time: 12:57
 */

namespace Hanaboso\PipesFramework\RabbitMq\Base;

use Bunny\Channel;
use Bunny\Client;
use Bunny\Message;
use Hanaboso\PipesFramework\RabbitMq\Base\ConsumerAbstract;
use Psr\Log\LoggerAwareInterface;

/**
 * Class AbstractConsumer
 *
 * @package RabbitMqBundle\Consumer
 */
abstract class Base2ConsumerAbstract extends ConsumerAbstract implements LoggerAwareInterface
{

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
     * @param null|string $setUpMethod
     * @param null|string $tickMethod
     * @param int|null    $tickSeconds
     * @param int|null    $maxMessages
     * @param int|null    $maxSeconds
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
        ?string $serializer = NULL,
        ?string $setUpMethod = NULL,
        ?string $tickMethod = NULL,
        ?int $tickSeconds = NULL,
        ?int $maxMessages = NULL,
        ?int $maxSeconds = NULL
    )
    {
        parent::__construct(
            $exchange,
            $routingKey,
            $queue,
            $consumerTag,
            $noLocal,
            $noAck,
            $exclusive,
            $nowait,
            $arguments,
            $prefetchCount,
            $prefetchSize,
            $serializer
        );
        $this->setUpMethod = $setUpMethod;
        $this->tickMethod  = $tickMethod;
        $this->tickSeconds = $tickSeconds;
        $this->maxMessages = $maxMessages;
        $this->maxSeconds  = $maxSeconds;
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
     * @param mixed   $data
     * @param Message $message
     * @param Channel $channel
     * @param Client  $client
     */
    abstract function handleMessage($data, Message $message, Channel $channel, Client $client): void;

}
