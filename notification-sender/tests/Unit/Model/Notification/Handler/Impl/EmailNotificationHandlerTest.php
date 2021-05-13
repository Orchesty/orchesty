<?php declare(strict_types=1);

namespace NotificationSenderTests\Unit\Model\Notification\Handler\Impl;

use Hanaboso\CommonsBundle\Enum\NotificationSenderEnum;
use Hanaboso\NotificationSender\Model\Notification\Dto\EmailDto;
use Hanaboso\NotificationSender\Model\Notification\Handler\Impl\EmailNotificationHandler;
use Hanaboso\Utils\String\Json;
use NotificationSenderTests\KernelTestCaseAbstract;

/**
 * Class EmailNotificationHandlerTest
 *
 * @package NotificationSenderTests\Unit\Model\Notification\Handler\Impl
 *
 * @covers  \Hanaboso\NotificationSender\Model\Notification\Handler\Impl\EmailNotificationHandler
 * @covers  \Hanaboso\NotificationSender\Model\Notification\Handler\EmailHandlerAbstract
 */
final class EmailNotificationHandlerTest extends KernelTestCaseAbstract
{

    /**
     * @var EmailNotificationHandler
     */
    private EmailNotificationHandler $handler;

    /**
     * @covers \Hanaboso\NotificationSender\Model\Notification\Handler\Impl\EmailNotificationHandler::getName
     */
    public function testGetName(): void
    {
        self::assertEquals('Email Sender', $this->handler->getName());
    }

    /**
     * @covers \Hanaboso\NotificationSender\Model\Notification\Handler\Impl\EmailNotificationHandler::getType
     */
    public function testGetType(): void
    {
        self::assertEquals(NotificationSenderEnum::EMAIL, $this->handler->getType());
    }

    /**
     * @covers \Hanaboso\NotificationSender\Model\Notification\Handler\Impl\EmailNotificationHandler::getRequiredSettings
     */
    public function testGetRequiredSettings(): void
    {
        self::assertEquals(
            [
                EmailDto::HOST,
                EmailDto::PORT,
                EmailDto::USERNAME,
                EmailDto::PASSWORD,
                EmailDto::ENCRYPTION,
                EmailDto::EMAILS,
                EmailDto::EMAIL,
            ],
            $this->handler->getRequiredSettings(),
        );
    }

    /**
     * @covers \Hanaboso\NotificationSender\Model\Notification\Handler\Impl\EmailNotificationHandler::process
     */
    public function testProcess(): void
    {
        self::assertEquals(
            new EmailDto('Something gone terribly wrong', Json::encode([])),
            $this->handler->process([]),
        );
    }

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new EmailNotificationHandler();
    }

}
