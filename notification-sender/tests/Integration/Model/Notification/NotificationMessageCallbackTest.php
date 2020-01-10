<?php declare(strict_types=1);

namespace Tests\Integration\Model\Notification;

use Exception;
use Hanaboso\CommonsBundle\Enum\NotificationEventEnum;
use Hanaboso\NotificationSender\Exception\NotificationException;
use Hanaboso\NotificationSender\Model\Notification\NotificationMessageCallback;
use RabbitMqBundle\Connection\Connection;
use RabbitMqBundle\Utils\Message;
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
        $message = Message::create(['pipes' => ['notification_type' => NotificationEventEnum::ACCESS_EXPIRATION]]);
        // phpcs:disable Squiz.NamingConventions.ValidVariableName.NotCamelCaps
        $message->delivery_info['delivery_tag'] = 'delivery_tag';
        // phpcs:enable

        $this->callback->processMessage(
            $message,
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
            Message::create('{}'),
            $this->connection,
            $this->connection->createChannel()
        );
    }

}
