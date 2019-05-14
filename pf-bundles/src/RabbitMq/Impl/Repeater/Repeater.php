<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\RabbitMq\Impl\Repeater;

use Bunny\Message;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RabbitMqBundle\Publisher\Publisher;

/**
 * Class Repeater
 *
 * @package Hanaboso\PipesFramework\RabbitMq\Impl\Repeater
 */
class Repeater implements LoggerAwareInterface
{

    public const MAX_HOP_FIELD           = 'max_hop';
    public const CURRENT_HOP_FIELD       = 'current_hop';
    public const DESTINATION_EXCHANGE    = 'repeater_destination_exchange';
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
     * @var Publisher
     */
    private $producer;

    /**
     * Repeater constructor.
     *
     * @param Publisher $producer
     * @param int       $hopLimit
     */
    public function __construct(Publisher $producer, int $hopLimit = 3)
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
        $this->producer->publish($message->content, $headers);

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
        return $message->hasHeader(self::DESTINATION_ROUTING_KEY) && $message->hasHeader(self::DESTINATION_EXCHANGE);
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
