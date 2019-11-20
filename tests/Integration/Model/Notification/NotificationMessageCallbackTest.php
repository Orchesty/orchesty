<?php declare(strict_types=1);

namespace Tests\Integration\Model\Notification;

use Bunny\Message;
use Exception;
use Hanaboso\CommonsBundle\Enum\NotificationEventEnum;
use Hanaboso\CommonsBundle\Utils\Json;
use Hanaboso\NotificationSender\Exception\NotificationException;
use Hanaboso\NotificationSender\Model\Notification\NotificationMessageCallback;
use RabbitMqBundle\Connection\Connection;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class NotificationMessageCallbackTest
 *
 * @package Tests\Integration\Model\Notification
 */
final class NotificationMessageCallbackTest extends DatabaseTestCaseAbstract
{

    /**
     * @var NotificationMessageCallback
     */
    private $callback;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->callback   = self::$container->get('notification.callback.message');
        $this->connection = self::$container->get('rabbit_mq.connection_manager')->getConnection();
    }

    /**
     * @covers NotificationMessageCallback::processMessage
     *
     * @throws Exception
     */
    public function testProcessMessage(): void
    {
        $this->callback->processMessage(
            new Message(
                '',
                '',
                FALSE,
                '',
                '',
                [],
                Json::encode(
                    [
                        'pipes' => [
                            'notification_type' => NotificationEventEnum::ACCESS_EXPIRATION,
                        ],
                    ]
                )
            ),
            $this->connection,
            $this->connection->createChannel()
        );

        self::assertTrue(TRUE); // No exception were thrown...
    }

    /**
     * @covers NotificationMessageCallback::processMessage
     *
     * @throws Exception
     */
    public function testProcessMessageNotFound(): void
    {
        self::expectException(NotificationException::class);
        self::expectExceptionCode(NotificationException::NOTIFICATION_EVENT_NOT_FOUND);
        self::expectExceptionMessage(
            "Notification event not found: RabbitMQ message missing required property 'notification_type'!"
        );

        $this->callback->processMessage(
            new Message(
                '',
                '',
                FALSE,
                '',
                '',
                [],
                Json::encode([])
            ),
            $this->connection,
            $this->connection->createChannel()
        );
    }

}
