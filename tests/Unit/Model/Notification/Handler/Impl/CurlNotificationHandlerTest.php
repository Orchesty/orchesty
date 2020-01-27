<?php declare(strict_types=1);

namespace NotificationSenderTests\Unit\Model\Notification\Handler\Impl;

use Hanaboso\CommonsBundle\Enum\NotificationSenderEnum;
use Hanaboso\NotificationSender\Model\Notification\Dto\CurlDto;
use Hanaboso\NotificationSender\Model\Notification\Handler\Impl\CurlNotificationHandler;
use NotificationSenderTests\KernelTestCaseAbstract;

/**
 * Class CurlNotificationHandlerTest
 *
 * @package NotificationSenderTests\Unit\Model\Notification\Handler\Impl
 *
 * @covers  \Hanaboso\NotificationSender\Model\Notification\Handler\Impl\CurlNotificationHandler
 * @covers  \Hanaboso\NotificationSender\Model\Notification\Handler\CurlHandlerAbstract
 */
final class CurlNotificationHandlerTest extends KernelTestCaseAbstract
{

    /**
     * @var CurlNotificationHandler
     */
    private CurlNotificationHandler $handler;

    /**
     * @covers \Hanaboso\NotificationSender\Model\Notification\Handler\Impl\CurlNotificationHandler::getName
     */
    public function testGetName(): void
    {
        self::assertEquals('CURL Sender', $this->handler->getName());
    }

    /**
     * @covers \Hanaboso\NotificationSender\Model\Notification\Handler\Impl\CurlNotificationHandler::getType
     */
    public function testGetType(): void
    {
        self::assertEquals(NotificationSenderEnum::CURL, $this->handler->getType());
    }

    /**
     * @covers \Hanaboso\NotificationSender\Model\Notification\Handler\Impl\CurlNotificationHandler::getRequiredSettings
     */
    public function testGetRequiredSettings(): void
    {
        self::assertEquals([CurlDto::METHOD, CurlDto::URL], $this->handler->getRequiredSettings());
    }

    /**
     * @covers \Hanaboso\NotificationSender\Model\Notification\Handler\Impl\CurlNotificationHandler::process
     */
    public function testProcess(): void
    {
        self::assertEquals(new CurlDto([], ['Content-Type' => 'application/json']), $this->handler->process([]));
    }

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new CurlNotificationHandler();
    }

}
