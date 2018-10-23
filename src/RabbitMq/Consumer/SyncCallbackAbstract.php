<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 29.8.17
 * Time: 11:26
 */

namespace Hanaboso\PipesFramework\RabbitMq\Consumer;

use Bunny\Channel;
use Bunny\Message;
use Exception;
use Hanaboso\PipesFramework\HbPFRabbitMqBundle\DebugMessageTrait;
use Hanaboso\PipesFramework\RabbitMq\CallbackStatus;
use Hanaboso\PipesFramework\RabbitMq\Exception\RabbitMqException;
use Hanaboso\PipesFramework\RabbitMq\Impl\Repeater\Repeater;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class BaseCallbackAbstract
 *
 * @package Hanaboso\PipesFramework\RabbitMq\Base
 */
abstract class SyncCallbackAbstract implements LoggerAwareInterface
{

    use DebugMessageTrait;

    /**
     * @var Repeater | NULL
     */
    protected $repeater = NULL;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * BaseCallbackAbstract constructor.
     */
    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    /**
     * @param mixed   $data
     * @param Message $message
     * @param Channel $channel
     *
     * @return CallbackStatus
     * @throws RabbitMqException
     * @throws Exception
     */
    final public function handleMessage($data, Message $message, Channel $channel): CallbackStatus
    {
        $channel;
        $result         = $this->handle($data, $message);
        $prepareMessage = $this->prepareMessage(
            '',
            $message->exchange,
            $message->routingKey,
            $message->headers
        );

        $prepareMessage['message'] = sprintf('return status:%s', $result->getStatus());
        $this->logger->debug('BaseCallback::handleMessage', $prepareMessage);

        switch ($result->getStatus()) {
            case CallbackStatus::SUCCESS:
                //TODO: what else
                break;
            case CallbackStatus::FAILED:
                //TODO: what else
                break;
            case CallbackStatus::RESEND:
                if ($this->getRepeater()) {
                    $prepareMessage['message'] = 'Repeat message';
                    $this->logger->debug('BaseCallback::handleMessage', $prepareMessage);

                    $this->getRepeater()->add($message);
                }
                break;
            default:
                $this->logger->error('BaseCallback::handleMessage', $prepareMessage);
                throw new RabbitMqException(
                    sprintf('Unknown callback status code: %s', $result->getStatus()),
                    RabbitMqException::UNKNOWN_CALLBACK_STATUS_CODE
                );
        }

        return $result;
    }

    /**
     * @return Repeater|NULL
     */
    public function getRepeater(): ?Repeater
    {
        return $this->repeater;
    }

    /**
     * @param Repeater $repeater
     *
     * @return SyncCallbackAbstract
     */
    public function setRepeater(Repeater $repeater): SyncCallbackAbstract
    {
        $this->repeater = $repeater;

        return $this;
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param mixed   $data
     * @param Message $message
     *
     * @return CallbackStatus
     */
    abstract function handle($data, Message $message): CallbackStatus;

}
