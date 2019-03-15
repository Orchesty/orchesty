<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\RabbitMq\Handler;

use Bunny\Channel;
use Exception;
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
     * @throws Exception
     */
    public function deleteQueues(array $queues): bool
    {
        /** @var Channel $ch */
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
     * @throws Exception
     */
    public function deleteExchange(string $exchange): bool
    {
        /** @var Channel $ch */
        $ch = $this->bunnyManager->getChannel();

        return (bool) $ch->exchangeDelete($exchange);
    }

}
