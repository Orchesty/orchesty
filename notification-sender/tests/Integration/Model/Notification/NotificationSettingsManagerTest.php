<?php declare(strict_types=1);

namespace Tests\Integration\Model\Notification;

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
use Tests\DatabaseTestCaseAbstract;
use Tests\Integration\Model\Notification\Handler\Impl\NullCurlHandler;
use Tests\Integration\Model\Notification\Handler\Impl\NullEmailHandler;
use Tests\Integration\Model\Notification\Handler\Impl\NullRabitHandler;

/**
 * Class NotificationSettingsManagerTest
 *
 * @package Tests\Integration\Model\Notification
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
     * @covers NotificationSettingsManager::listSettings
     *
     * @throws Exception
     */
    public function testListSettings(): void
    {
        $this->dm->persist(
            (new NotificationSettings())
                ->setClass(EmailNotificationHandler::class)
                ->setEvents(self::EVENTS)
                ->setSettings([EmailDto::EMAILS => ['one@example.com', 'two@example.com']])
        );

        $this->dm->persist(
            (new NotificationSettings())
                ->setClass('Unknown')
                ->setEvents(self::EVENTS)
                ->setSettings([EmailDto::EMAILS => ['one@example.com', 'two@example.com']])
        );

        $this->dm->flush();

        $handlers = $this->manager->listSettings();
        self::assertEquals(
            [
                [
                    'id'       => $handlers[0]['id'],
                    'created'  => $handlers[0]['created'],
                    'updated'  => $handlers[0]['updated'],
                    'type'     => NotificationSenderEnum::CURL,
                    'name'     => 'Curl Test Sender',
                    'class'    => NullCurlHandler::class,
                    'events'   => [],
                    'settings' => [],
                ], [
                    'id'       => $handlers[1]['id'],
                    'created'  => $handlers[1]['created'],
                    'updated'  => $handlers[1]['updated'],
                    'type'     => NotificationSenderEnum::EMAIL,
                    'name'     => 'Email Test Sender',
                    'class'    => NullEmailHandler::class,
                    'events'   => [],
                    'settings' => [],
                ], [
                    'id'       => $handlers[2]['id'],
                    'created'  => $handlers[2]['created'],
                    'updated'  => $handlers[2]['updated'],
                    'type'     => NotificationSenderEnum::RABBIT,
                    'name'     => 'Rabbit Test Sender',
                    'class'    => NullRabitHandler::class,
                    'events'   => [],
                    'settings' => [],
                ], [
                    'id'       => $handlers[3]['id'],
                    'created'  => $handlers[3]['created'],
                    'updated'  => $handlers[3]['updated'],
                    'type'     => NotificationSenderEnum::CURL,
                    'name'     => 'CURL Sender',
                    'class'    => CurlNotificationHandler::class,
                    'events'   => [],
                    'settings' => [],
                ], [
                    'id'       => $handlers[4]['id'],
                    'created'  => $handlers[4]['created'],
                    'updated'  => $handlers[4]['updated'],
                    'type'     => NotificationSenderEnum::EMAIL,
                    'name'     => 'Email Sender',
                    'class'    => EmailNotificationHandler::class,
                    'events'   => [
                        NotificationEventEnum::ACCESS_EXPIRATION,
                        NotificationEventEnum::DATA_ERROR,
                        NotificationEventEnum::SERVICE_UNAVAILABLE,
                    ],
                    'settings' => [
                        'emails' => ['one@example.com', 'two@example.com'],
                    ],
                ], [
                    'id'       => $handlers[5]['id'],
                    'created'  => $handlers[5]['created'],
                    'updated'  => $handlers[5]['updated'],
                    'type'     => NotificationSenderEnum::RABBIT,
                    'name'     => 'AMQP Sender',
                    'class'    => RabbitNotificationHandler::class,
                    'events'   => [],
                    'settings' => [],
                ],
            ],
            $handlers
        );

        self::assertCount(6, $this->dm->getRepository(NotificationSettings::class)->findAll());
    }

    /**
     * @covers NotificationSettingsManager::getSettings
     *
     * @throws Exception
     */
    public function testGetSettings(): void
    {
        $settings = (new NotificationSettings())
            ->setClass(EmailNotificationHandler::class)
            ->setEvents(self::EVENTS)
            ->setSettings([EmailDto::EMAILS => ['one@example.com', 'two@example.com']]);
        $this->dm->persist($settings);
        $this->dm->flush();
        $this->dm->clear();

        $settings = $this->manager->getSettings($settings->getId());

        self::assertEquals(
            [
                'id'       => $settings['id'],
                'created'  => $settings['created'],
                'updated'  => $settings['updated'],
                'type'     => NotificationSenderEnum::EMAIL,
                'name'     => 'Email Sender',
                'class'    => EmailNotificationHandler::class,
                'events'   => [
                    NotificationEventEnum::ACCESS_EXPIRATION,
                    NotificationEventEnum::DATA_ERROR,
                    NotificationEventEnum::SERVICE_UNAVAILABLE,
                ],
                'settings' => [
                    'emails' =>
                        [
                            'one@example.com',
                            'two@example.com',
                        ],
                ],
            ],
            $settings
        );
    }

    /**
     * @covers NotificationSettingsManager::getSettings
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
     * @covers NotificationSettingsManager::getSettings
     *
     * @throws Exception
     */
    public function testGetSettingsNotFoundHandler(): void
    {
        $settings = (new NotificationSettings())
            ->setClass('Unknown')
            ->setEvents(self::EVENTS)
            ->setSettings([EmailDto::EMAILS => ['one@example.com', 'two@example.com']]);
        $this->dm->persist($settings);
        $this->dm->flush();
        $this->dm->clear();

        self::expectException(NotificationException::class);
        self::expectExceptionCode(NotificationException::NOTIFICATION_HANDLER_NOT_FOUND);
        self::expectExceptionMessage("Notification handler 'Unknown' not found!");

        $this->manager->getSettings($settings->getId());
    }

    /**
     * @covers NotificationSettingsManager::saveSettings
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
                ]
            );
        $this->dm->persist($settings);
        $this->dm->flush();
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
                ],
            ]
        );

        self::assertEquals(
            [
                'id'       => $settings['id'],
                'created'  => $settings['created'],
                'updated'  => $settings['updated'],
                'type'     => NotificationSenderEnum::EMAIL,
                'name'     => 'Email Sender',
                'class'    => EmailNotificationHandler::class,
                'events'   => [NotificationEventEnum::ACCESS_EXPIRATION],
                'settings' => [
                    EmailDto::HOST       => 'host',
                    EmailDto::PORT       => 'port',
                    EmailDto::USERNAME   => 'username',
                    EmailDto::PASSWORD   => 'password',
                    EmailDto::ENCRYPTION => 'encryption',
                    EmailDto::EMAILS     => ['another-one@example.com', 'another-two@example.com'],
                ],
            ],
            $settings
        );
    }

    /**
     * @covers NotificationSettingsManager::saveSettings
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
     * @covers NotificationSettingsManager::saveSettings
     *
     * @throws Exception
     */
    public function testSaveSettingsNotFoundRequired(): void
    {
        $settings = (new NotificationSettings())
            ->setClass(EmailNotificationHandler::class)
            ->setEvents(self::EVENTS)
            ->setSettings([EmailDto::EMAILS => ['one@example.com', 'two@example.com']]);
        $this->dm->persist($settings);
        $this->dm->flush();
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
