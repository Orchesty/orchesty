<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\StatusService;

use Exception;
use Hanaboso\CommonsBundle\Enum\NotificationEventEnum;
use Hanaboso\CommonsBundle\Event\ProcessStatusEvent;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\String\Json;
use PhpAmqpLib\Message\AMQPMessage;
use RabbitMqBundle\Connection\Connection;
use RabbitMqBundle\Consumer\CallbackInterface;
use RabbitMqBundle\Publisher\Publisher;
use RabbitMqBundle\Utils\Message;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Class StatusServiceCallback
 *
 * @package Hanaboso\PipesPhpSdk\StatusService
 */
class StatusServiceCallback implements CallbackInterface
{

    private const MESSAGE           = 'message';
    private const NOTIFICATION_TYPE = 'notification_type';
    private const SUBJECT           = 'subject';
    private const PIPES             = 'pipes';
    private const SUCCESS           = 'success';
    private const PROCESS_ID        = 'process_id';

    /**
     * StatusServiceCallback constructor.
     *
     * @param EventDispatcherInterface $eventDispatcher
     * @param Publisher                $publisher
     */
    public function __construct(protected EventDispatcherInterface $eventDispatcher, private Publisher $publisher)
    {
    }

    /**
     * @param AMQPMessage $message
     * @param Connection  $connection
     * @param int         $channelId
     *
     * @throws Exception
     */
    public function processMessage(AMQPMessage $message, Connection $connection, int $channelId): void
    {
        $data = Json::decode(Message::getBody($message));

        if (!isset($data[self::PROCESS_ID])) {
            throw new PipesFrameworkException(
                'Missing message\'s content in StatusServiceCallback [process_id].',
                PipesFrameworkException::REQUIRED_PARAMETER_NOT_FOUND,
            );
        }

        if (!isset($data[self::SUCCESS])) {
            throw new PipesFrameworkException(
                'Missing message\'s content in StatusServiceCallback [success].',
                PipesFrameworkException::REQUIRED_PARAMETER_NOT_FOUND,
            );
        }

        $this->eventDispatcher->dispatch(
            new ProcessStatusEvent($data[self::PROCESS_ID], (bool) $data[self::SUCCESS]),
            ProcessStatusEvent::PROCESS_FINISHED,
        );

        if ($data[self::SUCCESS]) {
            $this->publish($data, 'Process done.', NotificationEventEnum::SUCCESS);
        } else {
            $this->publish($data, 'Something gone wrong!', NotificationEventEnum::UNKNOWN_ERROR);
        }

        Message::ack($message, $connection, $channelId);
    }

    /**
     * @param mixed[] $message
     * @param string  $subject
     * @param string  $type
     */
    private function publish(array $message, string $subject, string $type): void
    {
        $this->publisher->publish(
            Json::encode(
                [
                    self::PIPES => [
                        self::NOTIFICATION_TYPE => $type,
                        self::SUBJECT           => $subject,
                        self::MESSAGE           => $message,
                    ],
                ],
            ),
        );
    }

}
