<?php declare(strict_types=1);

namespace Demo\CustomNode;

use Hanaboso\CommonsBundle\Enum\NotificationEventEnum;
use Hanaboso\CommonsBundle\Event\ProcessStatusEvent;
use Hanaboso\PipesPhpSdk\StatusService\StatusServiceCallback as PipesStatusServiceCallback;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\String\Json;
use JsonException;
use PhpAmqpLib\Message\AMQPMessage;
use RabbitMqBundle\Connection\Connection;
use RabbitMqBundle\Publisher\Publisher;
use RabbitMqBundle\Utils\Message;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Class StatusServiceCallback
 *
 * @package Demo\CustomNode
 */
final class StatusServiceCallback extends PipesStatusServiceCallback
{

    private const MESSAGE              = 'message';
    private const MESSAGES             = 'messages';
    private const RESULT_CODE          = 'resultCode';
    private const ORIGINAL_RESULT_CODE = 'originalResultCode';
    private const NOTIFICATION_TYPE    = 'notification_type';
    private const SUBJECT              = 'subject';
    private const PIPES                = 'pipes';
    private const ERROR                = 1_006;

    /**
     * @var Publisher
     */
    private Publisher $publisher;

    /**
     * StatusServiceCallback constructor.
     *
     * @param EventDispatcherInterface $eventDispatcher
     * @param Publisher                $publisher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, Publisher $publisher)
    {
        parent::__construct($eventDispatcher);

        $this->publisher = $publisher;
    }

    /**
     * @param AMQPMessage $message
     * @param Connection  $connection
     * @param int         $channelId
     *
     * @throws JsonException
     * @throws PipesFrameworkException
     */
    public function processMessage(AMQPMessage $message, Connection $connection, int $channelId): void
    {
        $data = Json::decode(Message::getBody($message));

        foreach ($data[self::MESSAGES] ?? [] as $innerMessage) {
            $errors = [
                $innerMessage[self::RESULT_CODE] ?? 0,
                $innerMessage[self::ORIGINAL_RESULT_CODE] ?? 0,
            ];

            if (in_array(self::ERROR, $errors, TRUE)) {
                $this->publisher->publish(
                    Json::encode(
                        [
                            self::PIPES => [
                                self::NOTIFICATION_TYPE => NotificationEventEnum::DATA_ERROR,
                                self::SUBJECT           => 'Something gone wrong!',
                                self::MESSAGE           => $innerMessage,
                            ],
                        ]
                    )
                );
            }
        }

        if (!isset($data['process_id'])) {
            throw new PipesFrameworkException(
                "Missing message's content in StatusServiceCallback [process_id].",
                PipesFrameworkException::REQUIRED_PARAMETER_NOT_FOUND
            );
        }

        if (!isset($data['success'])) {
            throw new PipesFrameworkException(
                "Missing message's content in StatusServiceCallback [success].",
                PipesFrameworkException::REQUIRED_PARAMETER_NOT_FOUND
            );
        }

        $this->eventDispatcher->dispatch(
            new ProcessStatusEvent($data['process_id'], (bool) $data['success']),
            ProcessStatusEvent::PROCESS_FINISHED
        );

        Message::ack($message, $connection, $channelId);
    }

}
