<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 29.8.17
 * Time: 9:09
 */

namespace Hanaboso\PipesFramework\RabbitMq\Impl\Repeater;

use Bunny\Message;
use Hanaboso\PipesFramework\RabbitMq\Producer\AbstractProducer;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class Repeater
 *
 * @package Hanaboso\PipesFramework\RabbitMq\Repeater
 */
class Repeater implements LoggerAwareInterface
{

    /**
     * @var int
     */
    public const MAX_HOP_FIELD = 'max_hop';

    /**
     * @var int
     */
    public const CURRENT_HOP_FIELD = 'current_hop';

    /**
     * @var string
     */
    public const DESTINATION_EXCHANGE = 'repeater_destination_exchange';

    /**
     * @var string
     */
    public const DESTINATION_ROUTING_KEY = 'repeater_destination_rk';

    /**
     * @var int
     */
    protected $hopLimit;

    /**
     * @var int | null
     */
    protected $currentHop = NULL;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var \Hanaboso\PipesFramework\RabbitMq\Producer\AbstractProducer
     */
    private $producer;

    /**
     * Repeater constructor.
     *
     * @param \Hanaboso\PipesFramework\RabbitMq\Producer\AbstractProducer $producer
     * @param int                                                         $hopLimit
     */
    public function __construct(AbstractProducer $producer, int $hopLimit = 3)
    {
        $this->hopLimit = $hopLimit;
        $this->producer = $producer;
        $this->logger   = new NullLogger();
    }

    /**
     * @param Message $message
     *
     * @return bool
     */
    public function add(Message $message): bool
    {
        $headers = $message->headers;

        $headers[self::DESTINATION_EXCHANGE]    = $message->exchange;
        $headers[self::DESTINATION_ROUTING_KEY] = $message->routingKey;

        if ($message->hasHeader(self::MAX_HOP_FIELD)) {
            if (!isset($headers[self::CURRENT_HOP_FIELD])) {
                $headers[self::CURRENT_HOP_FIELD] = 0;
            }
            $headers[self::CURRENT_HOP_FIELD]++;

            if ($headers[self::CURRENT_HOP_FIELD] > $headers[self::MAX_HOP_FIELD]) {
                return FALSE;
            }
        } else {
            $headers[self::MAX_HOP_FIELD]     = $this->getHopLimit();
            $headers[self::CURRENT_HOP_FIELD] = 1;
        }

        //TODO: log
        $this->producer->publish($message->content, NULL, $headers);

        return TRUE;
    }

    /**
     * @return int
     */
    public function getHopLimit(): int
    {
        return $this->hopLimit;
    }

    /**
     * @param Message $message
     *
     * @return bool
     */
    public static function validRepeaterMessage(Message $message): bool
    {
        if (
            $message->hasHeader(self::DESTINATION_ROUTING_KEY) &&
            $message->hasHeader(self::DESTINATION_EXCHANGE)) {
            return TRUE;
        }

        return FALSE;
    }

    /**
     * Sets a logger instance on the object.
     *
     * @param LoggerInterface $logger
     *
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

}
