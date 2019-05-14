<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\RabbitMq\Impl\Repeater;

use Bunny\Message;
use Exception;
use Hanaboso\PipesFramework\RabbitMq\CallbackStatus;
use Hanaboso\PipesFramework\RabbitMq\Consumer\SyncCallbackAbstract;
use RabbitMqBundle\Consumer\DebugMessageTrait;
use RabbitMqBundle\Publisher\Publisher;

/**
 * Class RepeaterCallback
 *
 * @package Hanaboso\PipesFramework\RabbitMq\Impl\Repeater
 */
class RepeaterCallback extends SyncCallbackAbstract
{

    use DebugMessageTrait;

    /**
     * @var Publisher|null
     */
    protected $producer = NULL;

    /**
     * RepeaterCallback constructor.
     *
     * @param Publisher|null $producer
     */
    public function __construct(?Publisher $producer = NULL)
    {
        parent::__construct();
        $this->producer = $producer;
    }

    /**
     * @param mixed   $data
     * @param Message $message
     *
     * @return CallbackStatus
     * @throws Exception
     */
    public function handle($data, Message $message): CallbackStatus
    {
        $data;

        if (!Repeater::validRepeaterMessage($message)) {
            return new CallbackStatus(CallbackStatus::SUCCESS);
        }

        //TODO: refactor publishing
        if ($this->producer) {
            $routingKey = $message->getHeader(Repeater::DESTINATION_ROUTING_KEY);
            if ($routingKey) {
                $this->producer->setRoutingKey($routingKey);
            }

            $this->producer->setExchange($message->getHeader(Repeater::DESTINATION_EXCHANGE));
            $this->producer->publish($message->content, $message->headers);
        }

        return new CallbackStatus(CallbackStatus::SUCCESS);
    }

}
