<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 29.8.17
 * Time: 11:28
 */

namespace Hanaboso\PipesFramework\Commons\RabbitMq\Repeater;

use Bunny\Message;
use Hanaboso\PipesFramework\Commons\RabbitMq\BaseCallbackAbstract;
use Hanaboso\PipesFramework\Commons\RabbitMq\CallbackStatus;
use Hanaboso\PipesFramework\RabbitMqBundle\DebugMessageTrait;
use Hanaboso\PipesFramework\RabbitMqBundle\Producer\AbstractProducer;

/**
 * Class RepeaterCallback
 *
 * @package Hanaboso\PipesFramework\Commons\Repeater
 */
class RepeaterCallback extends BaseCallbackAbstract
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
     */
    function handle($data, Message $message): CallbackStatus
    {
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
