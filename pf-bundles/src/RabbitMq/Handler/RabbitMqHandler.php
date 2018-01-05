<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 11.10.17
 * Time: 15:15
 */

namespace Hanaboso\PipesFramework\RabbitMq\Handler;

use Hanaboso\PipesFramework\RabbitMq\BunnyManager;

/**
 * Class RabbitMqHandler
 *
 * @package Hanaboso\PipesFramework\RabbitMq\Handler
 */
class RabbitMqHandler
{

    /**
     * @var BunnyManager
     */
    protected $bunnyManager;

    /**
     * RabbitMqHandler constructor.
     *
     * @param BunnyManager $bunnyManager
     */
    public function __construct(BunnyManager $bunnyManager)
    {
        $this->bunnyManager = $bunnyManager;
    }

    /**
     * @param array $queues
     *
     * @return bool
     */
    public function deleteQueues(array $queues): bool
    {
        $ch = $this->bunnyManager->getChannel();
        foreach ($queues as $queue) {
            $ch->queueDelete($queue);
        }

        return TRUE;
    }

    /**
     * @param string $exchange
     *
     * @return bool
     */
    public function deleteExchange(string $exchange): bool
    {
        $ch     = $this->bunnyManager->getChannel();
        $result = $ch->exchangeDelete($exchange);

        return $result ? TRUE : FALSE;
    }

}
