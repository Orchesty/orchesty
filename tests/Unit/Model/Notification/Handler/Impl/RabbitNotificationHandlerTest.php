<?php declare(strict_types=1);

namespace NotificationSenderTests\Unit\Model\Notification\Handler\Impl;

use Hanaboso\CommonsBundle\Enum\NotificationSenderEnum;
use Hanaboso\NotificationSender\Model\Notification\Dto\RabbitDto;
use Hanaboso\NotificationSender\Model\Notification\Handler\Impl\RabbitNotificationHandler;
use NotificationSenderTests\KernelTestCaseAbstract;

/**
 * Class RabbitNotificationHandlerTest
 *
 * @package NotificationSenderTests\Unit\Model\Notification\Handler\Impl
 *
 * @covers  \Hanaboso\NotificationSender\Model\Notification\Handler\Impl\RabbitNotificationHandler
 * @covers  \Hanaboso\NotificationSender\Model\Notification\Handler\RabbitHandlerAbstract
 */
final class RabbitNotificationHandlerTest extends KernelTestCaseAbstract
{

    /**
     * @var RabbitNotificationHandler
     */
    private RabbitNotificationHandler $handler;

    /**
     * @covers \Hanaboso\NotificationSender\Model\Notification\Handler\Impl\RabbitNotificationHandler::getName
     */
    public function testGetName(): void
    {
        self::assertEquals('AMQP Sender', $this->handler->getName());
    }

    /**
     * @covers \Hanaboso\NotificationSender\Model\Notification\Handler\Impl\RabbitNotificationHandler::getType
     */
    public function testGetType(): void
    {
        self::assertEquals(NotificationSenderEnum::RABBIT, $this->handler->getType());
    }

    /**
     * @covers \Hanaboso\NotificationSender\Model\Notification\Handler\Impl\RabbitNotificationHandler::getRequiredSettings
     */
    public function testGetRequiredSettings(): void
    {
        self::assertEquals(
            [
                RabbitDto::HOST,
                RabbitDto::PORT,
                RabbitDto::VHOST,
                RabbitDto::USERNAME,
                RabbitDto::PASSWORD,
                RabbitDto::QUEUE,
            ],
            $this->handler->getRequiredSettings(),
        );
    }

    /**
     * @covers \Hanaboso\NotificationSender\Model\Notification\Handler\Impl\RabbitNotificationHandler::process
     */
    public function testProcess(): void
    {
        self::assertEquals(new RabbitDto([], ['Content-Type' => 'application/json']), $this->handler->process([]));
    }

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new RabbitNotificationHandler();
    }

}
