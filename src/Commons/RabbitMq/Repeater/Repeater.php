<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 29.8.17
 * Time: 9:09
 */

namespace Hanaboso\PipesFramework\Commons\RabbitMq\Repeater;

use Bunny\Message;
use Hanaboso\PipesFramework\RabbitMqBundle\Producer\AbstractProducer;

/**
 * Class Repeater
 *
 * @package Hanaboso\PipesFramework\Commons\RabbitMq\Repeater
 */
class Repeater
{

    /**
     *
     */
    public const MAX_HOP_FIELD = 'max_hop';
    /**
     *
     */
    public const CURRENT_HOP_FIELD = 'current_hop';

    /**
     * @var int
     */
    protected $hopLimit;

    /**
     * @var int | null
     */
    protected $currentHop = NULL;

    /**
     * @var AbstractProducer
     */
    private $producer;

    /**
     * Repeater constructor.
     *
     * @param AbstractProducer $producer
     * @param int              $hopLimit
     */
    public function __construct(AbstractProducer $producer, int $hopLimit = 3)
    {
        $this->hopLimit = $hopLimit;
        $this->producer = $producer;
    }

    /**
     * @param Message $message
     *
     * @return bool
     */
    public function add(Message $message): bool
    {
        $headers = $message->headers;

        if ($message->hasHeader(self::MAX_HOP_FIELD)) {
            $headers[self::CURRENT_HOP_FIELD]++;

            if ($headers[self::CURRENT_HOP_FIELD] > $headers[self::MAX_HOP_FIELD]) {
                return FALSE;
            }
        } else {
            $headers[self::MAX_HOP_FIELD]     = $this->getHopLimit();
            $headers[self::CURRENT_HOP_FIELD] = 1;
        }

        //TODO: log
        $this->producer->publish($message->content, $message->routingKey, $headers);

        return TRUE;
    }

    /**
     * @return int
     */
    public function getHopLimit(): int
    {
        return $this->hopLimit;
    }

}
