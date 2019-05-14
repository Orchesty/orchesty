<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\RabbitMq\Consumer;

use Bunny\Message;
use Hanaboso\PipesFramework\RabbitMq\CallbackStatus;
use Hanaboso\PipesFramework\RabbitMq\Exception\RabbitMqException;
use Hanaboso\PipesFramework\RabbitMq\Impl\Repeater\Repeater;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RabbitMqBundle\Connection\Connection;
use RabbitMqBundle\Consumer\CallbackInterface;
use RabbitMqBundle\Consumer\DebugMessageTrait;

/**
 * Class SyncCallbackAbstract
 *
 * @package Hanaboso\PipesFramework\RabbitMq\Consumer
 */
abstract class SyncCallbackAbstract implements CallbackInterface, LoggerAwareInterface
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
     * SyncCallbackAbstract constructor.
     */
    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    /**
     * @param Message    $message
     * @param Connection $connection
     * @param int        $channelId
     *
     * @return void
     * @throws RabbitMqException
     */
    public function processMessage(Message $message, Connection $connection, int $channelId): void
    {
        $result         = $this->handle($message->content, $message);
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
                $repeater = $this->getRepeater();
                if ($repeater) {
                    $prepareMessage['message'] = 'Repeat message';
                    $this->logger->debug('BaseCallback::handleMessage', $prepareMessage);

                    $repeater->add($message);
                }
                break;
            default:
                $this->logger->error('BaseCallback::handleMessage', $prepareMessage);
                throw new RabbitMqException(
                    sprintf('Unknown callback status code: %s', $result->getStatus()),
                    RabbitMqException::UNKNOWN_CALLBACK_STATUS_CODE
                );
        }

        // TODO: Should we acknowledge message like this?
        $connection->getChannel($channelId)->ack($message);
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
