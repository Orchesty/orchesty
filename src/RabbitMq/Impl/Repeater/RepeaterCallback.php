<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\RabbitMq\Impl\Repeater;

use Bunny\Message;
use Exception;
use Hanaboso\PipesFramework\HbPFRabbitMqBundle\DebugMessageTrait;
use Hanaboso\PipesFramework\RabbitMq\CallbackStatus;
use Hanaboso\PipesFramework\RabbitMq\Consumer\SyncCallbackAbstract;
use Hanaboso\PipesFramework\RabbitMq\Producer\AbstractProducer;

/**
 * Class RepeaterCallback
 *
 * @package Hanaboso\PipesFramework\RabbitMq\Impl\Repeater
 */
class RepeaterCallback extends SyncCallbackAbstract
{

    use DebugMessageTrait;

    /**
     * @var AbstractProducer|null
     */
    protected $producer = NULL;

    /**
     * RepeaterCallback constructor.
     *
     * @param AbstractProducer|null $producer
     */
    public function __construct(?AbstractProducer $producer = NULL)
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
    function handle($data, Message $message): CallbackStatus
    {
        $data;

        if (!Repeater::validRepeaterMessage($message)) {
            return new CallbackStatus(CallbackStatus::SUCCESS);
        }

        //TODO: refactor publishing
        if ($this->producer) {
            $this->producer->setExchange($message->getHeader(Repeater::DESTINATION_EXCHANGE));
            $this->producer->publish(
                $message->content,
                $message->getHeader(Repeater::DESTINATION_ROUTING_KEY),
                $message->headers
            );
        }

        return new CallbackStatus(CallbackStatus::SUCCESS);
    }

}
