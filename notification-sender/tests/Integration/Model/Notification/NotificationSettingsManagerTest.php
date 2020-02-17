<?php declare(strict_types=1);

namespace NotificationSenderTests\Integration\Model\Notification;

use Exception;
use Hanaboso\CommonsBundle\Enum\NotificationEventEnum;
use Hanaboso\CommonsBundle\Enum\NotificationSenderEnum;
use Hanaboso\NotificationSender\Document\NotificationSettings;
use Hanaboso\NotificationSender\Exception\NotificationException;
use Hanaboso\NotificationSender\Model\Notification\Dto\EmailDto;
use Hanaboso\NotificationSender\Model\Notification\Handler\Impl\CurlNotificationHandler;
use Hanaboso\NotificationSender\Model\Notification\Handler\Impl\EmailNotificationHandler;
use Hanaboso\NotificationSender\Model\Notification\Handler\Impl\RabbitNotificationHandler;
use Hanaboso\NotificationSender\Model\Notification\NotificationSettingsManager;
use NotificationSenderTests\DatabaseTestCaseAbstract;
use NotificationSenderTests\Integration\Model\Notification\Handler\Impl\NullCurlHandler;
use NotificationSenderTests\Integration\Model\Notification\Handler\Impl\NullEmailHandler;
use NotificationSenderTests\Integration\Model\Notification\Handler\Impl\NullRabitHandler;
use NotificationSenderTests\Integration\Model\Notification\Handler\Impl\NullUnknownHandler;

/**
 * Class NotificationSettingsManagerTest
 *
 * @package NotificationSenderTests\Integration\Model\Notification
 *
 * @covers  \Hanaboso\NotificationSender\Model\Notification\NotificationSettingsManager
 */
final class NotificationSettingsManagerTest extends DatabaseTestCaseAbstract
{

    private const EVENTS = [
        NotificationEventEnum::ACCESS_EXPIRATION,
        NotificationEventEnum::DATA_ERROR,
        NotificationEventEnum::SERVICE_UNAVAILABLE,
    ];

    /**
     * @var NotificationSettingsManager
     */
    private $manager;

    /**
     * @covers \Hanaboso\NotificationSender\Model\Notification\NotificationSettingsManager::listSettings
     *
     * @throws Exception
     */
    public function testListSettings(): void
    {
        $this->pfd(
            (new NotificationSettings())
                ->setClass(EmailNotificationHandler::class)
                ->setEvents(self::EVENTS)
                ->setSettings([EmailDto::EMAILS => ['one@example.com', 'two@example.com']])
        );

        $this->pfd(
            (new NotificationSettings())
                ->setClass('Unknown')
                ->setEvents(self::EVENTS)
                ->setSettings([EmailDto::EMAILS => ['one@example.com', 'two@example.com']])
        );

        $handlers = $this->manager->listSettings();
        self::assertEquals(
            [
                [
                    NotificationSettings::ID         => $handlers[0][NotificationSettings::ID],
                    NotificationSettings::CREATED    => $handlers[0][NotificationSettings::CREATED],
                    NotificationSettings::UPDATED    => $handlers[0][NotificationSettings::UPDATED],
                    NotificationSettings::TYPE       => NotificationSenderEnum::CURL,
                    NotificationSettings::NAME       => 'Curl Test Sender',
                    NotificationSettings::CLASS_NAME => NullCurlHandler::class,
                    NotificationSettings::EVENTS     => [],
                    NotificationSettings::SETTINGS   => [],
                ], [
                    NotificationSettings::ID         => $handlers[1][NotificationSettings::ID],
                    NotificationSettings::CREATED    => $handlers[1][NotificationSettings::CREATED],
                    NotificationSettings::UPDATED    => $handlers[1][NotificationSettings::UPDATED],
                    NotificationSettings::TYPE       => NotificationSenderEnum::EMAIL,
                    NotificationSettings::NAME       => 'Email Test Sender',
                    NotificationSettings::CLASS_NAME => NullEmailHandler::class,
                    NotificationSettings::EVENTS     => [],
                    NotificationSettings::SETTINGS   => [],
                ], [
                    NotificationSettings::ID         => $handlers[2][NotificationSettings::ID],
                    NotificationSettings::CREATED    => $handlers[2][NotificationSettings::CREATED],
                    NotificationSettings::UPDATED    => $handlers[2][NotificationSettings::UPDATED],
                    NotificationSettings::TYPE       => NotificationSenderEnum::RABBIT,
                    NotificationSettings::NAME       => 'Rabbit Test Sender',
                    NotificationSettings::CLASS_NAME => NullRabitHandler::class,
                    NotificationSettings::EVENTS     => [],
                    NotificationSettings::SETTINGS   => [],
                ], [
                    NotificationSettings::ID         => $handlers[3][NotificationSettings::ID],
                    NotificationSettings::CREATED    => $handlers[3][NotificationSettings::CREATED],
                    NotificationSettings::UPDATED    => $handlers[3][NotificationSettings::UPDATED],
                    NotificationSettings::TYPE       => 'Unknown',
                    NotificationSettings::NAME       => 'Unknown Test Sender',
                    NotificationSettings::CLASS_NAME => NullUnknownHandler::class,
                    NotificationSettings::EVENTS     => [],
                    NotificationSettings::SETTINGS   => [],
                ], [
                    NotificationSettings::ID         => $handlers[4][NotificationSettings::ID],
                    NotificationSettings::CREATED    => $handlers[4][NotificationSettings::CREATED],
                    NotificationSettings::UPDATED    => $handlers[4][NotificationSettings::UPDATED],
                    NotificationSettings::TYPE       => NotificationSenderEnum::CURL,
                    NotificationSettings::NAME       => 'CURL Sender',
                    NotificationSettings::CLASS_NAME => CurlNotificationHandler::class,
                    NotificationSettings::EVENTS     => [],
                    NotificationSettings::SETTINGS   => [],
                ], [
                    NotificationSettings::ID         => $handlers[5][NotificationSettings::ID],
                    NotificationSettings::CREATED    => $handlers[5][NotificationSettings::CREATED],
                    NotificationSettings::UPDATED    => $handlers[5][NotificationSettings::UPDATED],
                    NotificationSettings::TYPE       => NotificationSenderEnum::EMAIL,
                    NotificationSettings::NAME       => 'Email Sender',
                    NotificationSettings::CLASS_NAME => EmailNotificationHandler::class,
                    NotificationSettings::EVENTS     => [
                        NotificationEventEnum::ACCESS_EXPIRATION,
                        NotificationEventEnum::DATA_ERROR,
                        NotificationEventEnum::SERVICE_UNAVAILABLE,
                    ],
                    NotificationSettings::SETTINGS   => [
                        EmailDto::EMAILS => ['one@example.com', 'two@example.com'],
                    ],
                ], [
                    NotificationSettings::ID         => $handlers[6][NotificationSettings::ID],
                    NotificationSettings::CREATED    => $handlers[6][NotificationSettings::CREATED],
                    NotificationSettings::UPDATED    => $handlers[6][NotificationSettings::UPDATED],
                    NotificationSettings::TYPE       => NotificationSenderEnum::RABBIT,
                    NotificationSettings::NAME       => 'AMQP Sender',
                    NotificationSettings::CLASS_NAME => RabbitNotificationHandler::class,
                    NotificationSettings::EVENTS     => [],
                    NotificationSettings::SETTINGS   => [],
                ],
            ],
            $handlers
        );

        self::assertCount(7, $this->dm->getRepository(NotificationSettings::class)->findAll());
    }

    /**
     * @covers \Hanaboso\NotificationSender\Document\NotificationSettings::getSettings
     *
     * @throws Exception
     */
    public function testGetSettings(): void
    {
        $settings = (new NotificationSettings())
            ->setClass(EmailNotificationHandler::class)
            ->setEvents(self::EVENTS)
            ->setSettings([EmailDto::EMAILS => ['one@example.com', 'two@example.com']]);
        $this->pfd($settings);
        $this->dm->clear();

        $settings = $this->manager->getSettings($settings->getId());

        self::assertEquals(
            [
                NotificationSettings::ID         => $settings[NotificationSettings::ID],
                NotificationSettings::CREATED    => $settings[NotificationSettings::CREATED],
                NotificationSettings::UPDATED    => $settings[NotificationSettings::UPDATED],
                NotificationSettings::TYPE       => NotificationSenderEnum::EMAIL,
                NotificationSettings::NAME       => 'Email Sender',
                NotificationSettings::CLASS_NAME => EmailNotificationHandler::class,
                NotificationSettings::EVENTS     => [
                    NotificationEventEnum::ACCESS_EXPIRATION,
                    NotificationEventEnum::DATA_ERROR,
                    NotificationEventEnum::SERVICE_UNAVAILABLE,
                ],
                NotificationSettings::SETTINGS   => [
                    EmailDto::EMAILS => ['one@example.com', 'two@example.com'],
                ],
            ],
            $settings
        );
    }

    /**
     * @covers \Hanaboso\NotificationSender\Model\Notification\NotificationSettingsManager::getSettings
     *
     * @throws Exception
     */
    public function testGetSettingsNotFound(): void
    {
        self::expectException(NotificationException::class);
        self::expectExceptionCode(NotificationException::NOTIFICATION_SETTINGS_NOT_FOUND);
        self::expectExceptionMessage("NotificationSettings with key 'Unknown' not found!");

        $this->manager->getSettings('Unknown');
    }

    /**
     * @covers \Hanaboso\NotificationSender\Model\Notification\NotificationSettingsManager::getSettings
     *
     * @throws Exception
     */
    public function testGetSettingsNotFoundHandler(): void
    {
        $settings = (new NotificationSettings())
            ->setClass('Unknown')
            ->setEvents(self::EVENTS)
            ->setSettings([EmailDto::EMAILS => ['one@example.com', 'two@example.com']]);
        $this->pfd($settings);
        $this->dm->clear();

        self::expectException(NotificationException::class);
        self::expectExceptionCode(NotificationException::NOTIFICATION_HANDLER_NOT_FOUND);
        self::expectExceptionMessage("Notification handler 'Unknown' not found!");

        $this->manager->getSettings($settings->getId());
    }

    /**
     * @covers \Hanaboso\NotificationSender\Model\Notification\NotificationSettingsManager::saveSettings
     *
     * @throws Exception
     */
    public function testSaveSettings(): void
    {
        $settings = (new NotificationSettings())
            ->setClass(EmailNotificationHandler::class)
            ->setEvents(self::EVENTS)
            ->setSettings(
                [
                    EmailDto::HOST       => 'host',
                    EmailDto::PORT       => 'port',
                    EmailDto::USERNAME   => 'username',
                    EmailDto::PASSWORD   => 'password',
                    EmailDto::ENCRYPTION => 'encryption',
                    EmailDto::EMAILS     => ['one@example.com', 'two@example.com'],
                    EmailDto::EMAIL      => 'email@example.com',
                ]
            );

        $this->pfd($settings);
        $this->dm->clear();

        $settings = $this->manager->saveSettings(
            $settings->getId(),
            [
                NotificationSettings::EVENTS   => [NotificationEventEnum::ACCESS_EXPIRATION],
                NotificationSettings::SETTINGS => [
                    EmailDto::HOST       => 'host',
                    EmailDto::PORT       => 'port',
                    EmailDto::USERNAME   => 'username',
                    EmailDto::PASSWORD   => 'password',
                    EmailDto::ENCRYPTION => 'encryption',
                    EmailDto::EMAILS     => ['another-one@example.com', 'another-two@example.com'],
                    EmailDto::EMAIL      => 'email@example.com',
                    'Unknown'            => 'unknown',
                ],
            ]
        );

        self::assertEquals(
            [
                NotificationSettings::ID         => $settings[NotificationSettings::ID],
                NotificationSettings::CREATED    => $settings[NotificationSettings::CREATED],
                NotificationSettings::UPDATED    => $settings[NotificationSettings::UPDATED],
                NotificationSettings::TYPE       => NotificationSenderEnum::EMAIL,
                NotificationSettings::NAME       => 'Email Sender',
                NotificationSettings::CLASS_NAME => EmailNotificationHandler::class,
                NotificationSettings::EVENTS     => [NotificationEventEnum::ACCESS_EXPIRATION],
                NotificationSettings::SETTINGS   => [
                    EmailDto::HOST       => 'host',
                    EmailDto::PORT       => 'port',
                    EmailDto::USERNAME   => 'username',
                    EmailDto::PASSWORD   => 'password',
                    EmailDto::ENCRYPTION => 'encryption',
                    EmailDto::EMAILS     => ['another-one@example.com', 'another-two@example.com'],
                    EmailDto::EMAIL      => 'email@example.com',
                ],
            ],
            $settings
        );
    }

    /**
     * @covers \Hanaboso\NotificationSender\Model\Notification\NotificationSettingsManager::saveSettings
     *
     * @throws Exception
     */
    public function testSaveSettingsNotFound(): void
    {
        self::expectException(NotificationException::class);
        self::expectExceptionCode(NotificationException::NOTIFICATION_SETTINGS_NOT_FOUND);
        self::expectExceptionMessage("NotificationSettings with key 'Unknown' not found!");

        $this->manager->saveSettings('Unknown', []);
    }

    /**
     * @covers \Hanaboso\NotificationSender\Model\Notification\NotificationSettingsManager::saveSettings
     *
     * @throws Exception
     */
    public function testSaveSettingsNotFoundRequired(): void
    {
        $settings = (new NotificationSettings())
            ->setClass(EmailNotificationHandler::class)
            ->setEvents(self::EVENTS)
            ->setSettings([EmailDto::EMAILS => ['one@example.com', 'two@example.com']]);
        $this->pfd($settings);
        $this->dm->clear();

        self::expectException(NotificationException::class);
        self::expectExceptionCode(NotificationException::NOTIFICATION_PARAMETER_NOT_FOUND);
        self::expectExceptionMessage("Required settings 'host' for type 'email' is missing!");

        $this->manager->saveSettings($settings->getId(), [NotificationSettings::SETTINGS => []]);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = self::$container->get('notification.manager.settings');
    }

}
