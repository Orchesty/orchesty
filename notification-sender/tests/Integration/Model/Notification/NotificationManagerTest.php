<?php declare(strict_types=1);

namespace NotificationSenderTests\Integration\Model\Notification;

use Exception;
use Hanaboso\CommonsBundle\Enum\NotificationEventEnum;
use Hanaboso\NotificationSender\Document\NotificationSettings;
use Hanaboso\NotificationSender\Model\Notification\Dto\CurlDto;
use Hanaboso\NotificationSender\Model\Notification\Dto\EmailDto;
use Hanaboso\NotificationSender\Model\Notification\Dto\RabbitDto;
use Hanaboso\NotificationSender\Model\Notification\NotificationManager;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\CustomAssertTrait;
use NotificationSenderTests\DatabaseTestCaseAbstract;
use NotificationSenderTests\Integration\Model\Notification\Handler\Impl\NullCurlHandler;
use NotificationSenderTests\Integration\Model\Notification\Handler\Impl\NullEmailHandler;
use NotificationSenderTests\Integration\Model\Notification\Handler\Impl\NullRabitHandler;
use NotificationSenderTests\Integration\Model\Notification\Handler\Impl\NullUnknownHandler;

/**
 * Class NotificationManagerTest
 *
 * @package NotificationSenderTests\Integration\Model\Notification
 *
 * @covers  \Hanaboso\NotificationSender\Model\Notification\NotificationManager
 * @covers  \Hanaboso\NotificationSender\Model\Notification\Dto\CurlDto
 * @covers  \Hanaboso\NotificationSender\Model\Notification\Dto\EmailDto
 * @covers  \Hanaboso\NotificationSender\Model\Notification\Dto\RabbitDto
 * @covers  \Hanaboso\NotificationSender\Model\Notification\Sender\CurlSender
 * @covers  \Hanaboso\NotificationSender\Model\Notification\Sender\EmailSender
 * @covers  \Hanaboso\NotificationSender\Model\Notification\Sender\RabbitSender
 */
final class NotificationManagerTest extends DatabaseTestCaseAbstract
{

    use CustomAssertTrait;

    private const EVENTS = [
        NotificationEventEnum::ACCESS_EXPIRATION,
        NotificationEventEnum::DATA_ERROR,
        NotificationEventEnum::SERVICE_UNAVAILABLE,
    ];

    /**
     * @var NotificationManager
     */
    private $manager;

    /**
     * @covers \Hanaboso\NotificationSender\Model\Notification\NotificationManager::send
     *
     * @throws Exception
     */
    public function testSend(): void
    {
        $this->pfd(
            (new NotificationSettings())
                ->setClass(NullCurlHandler::class)
                ->setEvents(self::EVENTS)
                ->setSettings(
                    [
                        CurlDto::METHOD  => 'POST',
                        CurlDto::URL     => 'https://example.com',
                        CurlDto::HEADERS => [],
                    ]
                )
        );

        $this->pfd(
            (new NotificationSettings())
                ->setClass(NullEmailHandler::class)
                ->setEvents(self::EVENTS)
                ->setSettings(
                    [
                        EmailDto::HOST       => 'mailhog',
                        EmailDto::PORT       => '1025',
                        EmailDto::USERNAME   => 'root',
                        EmailDto::PASSWORD   => 'root',
                        EmailDto::ENCRYPTION => 'null',
                        EmailDto::EMAILS     => ['one@example.com', 'two@example.com'],
                        EmailDto::EMAIL      => 'email@example.com',
                    ]
                )
        );

        $this->pfd(
            (new NotificationSettings())
                ->setClass(NullRabitHandler::class)
                ->setEvents(self::EVENTS)
                ->setSettings(
                    [
                        RabbitDto::HOST     => 'rabbitmq',
                        RabbitDto::PORT     => '5672',
                        RabbitDto::USERNAME => 'guest',
                        RabbitDto::PASSWORD => 'guest',
                        RabbitDto::VHOST    => '/',
                        RabbitDto::QUEUE    => 'queue',
                    ]
                )
        );

        $this->pfd(
            (new NotificationSettings())
                ->setClass(NullUnknownHandler::class)
                ->setEvents(self::EVENTS)
                ->setSettings([])
        );

        $this->manager->send(NotificationEventEnum::ACCESS_EXPIRATION, []);

        self::assertFake();
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = self::$container->get('notification.manager');
    }

}
