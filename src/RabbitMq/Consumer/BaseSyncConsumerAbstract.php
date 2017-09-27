<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: pavel.severyn
 * Date: 29.8.17
 * Time: 10:28
 */

namespace Hanaboso\PipesFramework\RabbitMq\Consumer;

use Bunny\Channel;
use Bunny\Client;
use Bunny\Message;
use Hanaboso\PipesFramework\RabbitMq\CallbackStatus;
use Hanaboso\PipesFramework\RabbitMq\Exception\RabbitMqException;

/**
 * Class BaseConsumerAbstract
 *
 * @package Hanaboso\PipesFramework\RabbitMq\Base
 */
abstract class BaseSyncConsumerAbstract extends SyncConsumerAbstract
{

    /**
     * @var callable
     */
    protected $callback;

    /**
     * @param mixed   $data
     * @param Message $message
     * @param Channel $channel
     * @param Client  $client
     *
     * @throws RabbitMqException
     */
    public function handleMessage($data, Message $message, Channel $channel, Client $client): void
    {
        if (!is_callable($this->getCallback())) {
            throw new RabbitMqException(
                'Missing callback definition',
                RabbitMqException::MISSING_CALLBACK_DEFINITION
            );
        }

        $result = call_user_func($this->getCallback(), $data, $message, $channel, $client);
        $this->handleResult($result, $message, $channel);
    }

    /**
     * @return callable|null
     */
    public function getCallback(): ?callable
    {
        return $this->callback;
    }

    /**
     * @param callable $callback
     *
     * @return BaseSyncConsumerAbstract
     */
    public function setCallback(callable $callback): BaseSyncConsumerAbstract
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * @param CallbackStatus $result
     * @param Message        $message
     * @param Channel        $channel
     *
     * @return void
     */
    protected function handleResult(CallbackStatus $result, Message $message, Channel $channel): void
    {
        if (!$this->isNoAck()) {
            $channel->ack($message);
        }
    }

}
