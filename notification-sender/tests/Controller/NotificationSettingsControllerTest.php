<?php declare(strict_types=1);

namespace Tests\Controller;

use Exception;
use Hanaboso\CommonsBundle\Enum\NotificationEventEnum;
use Hanaboso\CommonsBundle\Enum\NotificationSenderEnum;
use Hanaboso\CommonsBundle\Utils\ControllerUtils;
use Hanaboso\NotificationSender\Document\NotificationSettings;
use Hanaboso\NotificationSender\Exception\NotificationException;
use Hanaboso\NotificationSender\Model\Notification\Dto\EmailDto;
use Hanaboso\NotificationSender\Model\Notification\Handler\Impl\CurlNotificationHandler;
use Hanaboso\NotificationSender\Model\Notification\Handler\Impl\EmailNotificationHandler;
use Hanaboso\NotificationSender\Model\Notification\Handler\Impl\RabbitNotificationHandler;
use Tests\ControllerTestCaseAbstract;
use Tests\Integration\Model\Notification\Handler\Impl\NullCurlHandler;
use Tests\Integration\Model\Notification\Handler\Impl\NullEmailHandler;
use Tests\Integration\Model\Notification\Handler\Impl\NullRabitHandler;

/**
 * Class NotificationSettingsControllerTest
 *
 * @package Tests\Controller
 */
final class NotificationSettingsControllerTest extends ControllerTestCaseAbstract
{

    private const ID      = 'id';
    private const ITEMS   = 'items';
    private const CREATED = 'created';
    private const UPDATED = 'updated';

    /**
     * @covers NotificationSettingsController::listSettingsAction
     * @covers NotificationSettingsHandler::listSettings
     * @covers NotificationSettingsManager::listSettings
     *
     * @throws Exception
     */
    public function testListSettings(): void
    {
        $response = $this->sendGet('/notifications/settings');

        $this->assertResponse(
            $response,
            200,
            [
                self::ITEMS => [
                    [
                        self::ID      => $response->getContent()[self::ITEMS][0][self::ID],
                        self::CREATED => $response->getContent()[self::ITEMS][0][self::CREATED],
                        self::UPDATED => $response->getContent()[self::ITEMS][0][self::UPDATED],
                        'type'        => NotificationSenderEnum::CURL,
                        'name'        => 'Curl Test Sender',
                        'class'       => NullCurlHandler::class,
                        'events'      => [],
                        'settings'    => [],
                    ],
                    [
                        self::ID      => $response->getContent()[self::ITEMS][1][self::ID],
                        self::CREATED => $response->getContent()[self::ITEMS][1][self::CREATED],
                        self::UPDATED => $response->getContent()[self::ITEMS][1][self::UPDATED],
                        'type'        => NotificationSenderEnum::EMAIL,
                        'name'        => 'Email Test Sender',
                        'class'       => NullEmailHandler::class,
                        'events'      => [],
                        'settings'    => [],
                    ],
                    [
                        self::ID      => $response->getContent()[self::ITEMS][2][self::ID],
                        self::CREATED => $response->getContent()[self::ITEMS][2][self::CREATED],
                        self::UPDATED => $response->getContent()[self::ITEMS][2][self::UPDATED],
                        'type'        => NotificationSenderEnum::RABBIT,
                        'name'        => 'Rabbit Test Sender',
                        'class'       => NullRabitHandler::class,
                        'events'      => [],
                        'settings'    => [],
                    ], [
                        self::ID      => $response->getContent()[self::ITEMS][3][self::ID],
                        self::CREATED => $response->getContent()[self::ITEMS][3][self::CREATED],
                        self::UPDATED => $response->getContent()[self::ITEMS][3][self::UPDATED],
                        'type'        => NotificationSenderEnum::CURL,
                        'name'        => 'CURL Sender',
                        'class'       => CurlNotificationHandler::class,
                        'events'      => [],
                        'settings'    => [],
                    ], [
                        self::ID      => $response->getContent()[self::ITEMS][4][self::ID],
                        self::CREATED => $response->getContent()[self::ITEMS][4][self::CREATED],
                        self::UPDATED => $response->getContent()[self::ITEMS][4][self::UPDATED],
                        'type'        => NotificationSenderEnum::EMAIL,
                        'name'        => 'Email Sender',
                        'class'       => EmailNotificationHandler::class,
                        'events'      => [],
                        'settings'    => [],
                    ], [
                        self::ID      => $response->getContent()[self::ITEMS][5][self::ID],
                        self::CREATED => $response->getContent()[self::ITEMS][5][self::CREATED],
                        self::UPDATED => $response->getContent()[self::ITEMS][5][self::UPDATED],
                        'type'        => NotificationSenderEnum::RABBIT,
                        'name'        => 'AMQP Sender',
                        'class'       => RabbitNotificationHandler::class,
                        'events'      => [],
                        'settings'    => [],
                    ],
                ],
            ]
        );
    }

    /**
     * @covers NotificationSettingsController::getSettingsAction
     * @covers NotificationSettingsHandler::getSettings
     * @covers NotificationSettingsManager::getSettings
     *
     * @throws Exception
     */
    public function testGetSettings(): void
    {
        $response = $this->sendGet('/notifications/settings');
        $response = $this->sendGet(
            sprintf(
                '/notifications/settings/%s',
                $response->getContent()[self::ITEMS][1][self::ID]
            )
        );

        $this->assertResponse(
            $response,
            200,
            [
                self::ID      => $response->getContent()[self::ID],
                self::CREATED => $response->getContent()[self::CREATED],
                self::UPDATED => $response->getContent()[self::UPDATED],
                'type'        => NotificationSenderEnum::EMAIL,
                'name'        => 'Email Test Sender',
                'class'       => NullEmailHandler::class,
                'events'      => [],
                'settings'    => [],
            ]
        );
    }

    /**
     * @covers NotificationSettingsController::getSettingsAction
     * @covers NotificationSettingsHandler::getSettings
     * @covers NotificationSettingsManager::getSettings
     *
     * @throws Exception
     */
    public function testGetSettingsNotFound(): void
    {
        $this->assertResponse(
            $this->sendGet('/notifications/settings/Unknown'),
            404,
            [
                'status'     => ControllerUtils::NOT_FOUND,
                'error_code' => 105,
                'type'       => NotificationException::class,
                'message'    => "NotificationSettings with key 'Unknown' not found!",
            ]
        );
    }

    /**
     * @covers NotificationSettingsController::saveSettingsAction
     * @covers NotificationSettingsHandler::saveSettings
     * @covers NotificationSettingsManager::saveSettings
     *
     * @throws Exception
     */
    public function testSaveSettings(): void
    {
        $response = $this->sendGet('/notifications/settings');
        $response = $this->sendPut(
            sprintf(
                '/notifications/settings/%s',
                $response->getContent()[self::ITEMS][1][self::ID]
            ),
            [
                NotificationSettings::EVENTS   => [NotificationEventEnum::ACCESS_EXPIRATION],
                NotificationSettings::SETTINGS => [
                    EmailDto::HOST       => 'host',
                    EmailDto::PORT       => 'port',
                    EmailDto::USERNAME   => 'username',
                    EmailDto::PASSWORD   => 'password',
                    EmailDto::ENCRYPTION => 'ssl',
                    EmailDto::EMAILS     => [
                        'another-one@example.com', 'another-two@example.com',
                    ],
                ],
            ]
        );

        $this->assertResponse(
            $response,
            200,
            [
                self::ID      => $response->getContent()[self::ID],
                self::CREATED => $response->getContent()[self::CREATED],
                self::UPDATED => $response->getContent()[self::UPDATED],
                'type'        => NotificationSenderEnum::EMAIL,
                'name'        => 'Email Test Sender',
                'class'       => NullEmailHandler::class,
                'events'      => [NotificationEventEnum::ACCESS_EXPIRATION],
                'settings'    => [
                    EmailDto::HOST       => 'host',
                    EmailDto::PORT       => 'port',
                    EmailDto::USERNAME   => 'username',
                    EmailDto::PASSWORD   => 'password',
                    EmailDto::ENCRYPTION => 'ssl',
                    EmailDto::EMAILS     => [
                        'another-one@example.com', 'another-two@example.com',
                    ],
                ],
            ]
        );
    }

    /**
     * @covers NotificationSettingsController::saveSettingsAction
     * @covers NotificationSettingsHandler::saveSettings
     * @covers NotificationSettingsManager::saveSettings
     *
     * @throws Exception
     */
    public function testSaveSettingsNotFound(): void
    {
        $this->assertResponse(
            $this->sendPut('/notifications/settings/Unknown'),
            404,
            [
                'status'     => ControllerUtils::NOT_FOUND,
                'error_code' => 105,
                'type'       => NotificationException::class,
                'message'    => "NotificationSettings with key 'Unknown' not found!",
            ]
        );
    }

    /**
     * @covers NotificationSettingsController::saveSettingsAction
     * @covers NotificationSettingsHandler::saveSettings
     * @covers NotificationSettingsManager::saveSettings
     *
     * @throws Exception
     */
    public function testSaveSettingsNotFoundRequired(): void
    {
        $response = $this->sendGet('/notifications/settings');
        $response = $this->sendPut(
            sprintf(
                '/notifications/settings/%s',
                $response->getContent()[self::ITEMS][1][self::ID]
            ),
            [
                NotificationSettings::SETTINGS => [],
            ]
        );

        $this->assertResponse(
            $response,
            404,
            [
                'status'     => ControllerUtils::NOT_FOUND,
                'error_code' => 101,
                'type'       => NotificationException::class,
                'message'    => "Required settings 'host' for type 'email' is missing!",
            ]
        );
    }

}
