<?php declare(strict_types=1);

namespace Tests\Controller;

use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Exception;
use Hanaboso\CommonsBundle\Enum\NotificationEventEnum;
use Hanaboso\CommonsBundle\Enum\NotificationSenderEnum;
use Hanaboso\NotificationSender\Document\NotificationSettings;
use Hanaboso\NotificationSender\Exception\NotificationException;
use Hanaboso\NotificationSender\Model\Notification\Dto\EmailDto;
use Hanaboso\NotificationSender\Model\Notification\Handler\Impl\HanabosoNotificationHandler;
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

        $this->assertResponse($response, 200, [
            'items' => [
                [
                    'id'       => $response->getContent()['items'][0]['id'],
                    'created'  => $response->getContent()['items'][0]['created'],
                    'updated'  => $response->getContent()['items'][0]['updated'],
                    'type'     => NotificationSenderEnum::CURL,
                    'name'     => 'Curl Test Sender',
                    'class'    => NullCurlHandler::class,
                    'events'   => [],
                    'settings' => [],
                ], [
                    'id'       => $response->getContent()['items'][1]['id'],
                    'created'  => $response->getContent()['items'][1]['created'],
                    'updated'  => $response->getContent()['items'][1]['updated'],
                    'type'     => NotificationSenderEnum::EMAIL,
                    'name'     => 'Email Test Sender',
                    'class'    => NullEmailHandler::class,
                    'events'   => [],
                    'settings' => [],
                ], [
                    'id'       => $response->getContent()['items'][2]['id'],
                    'created'  => $response->getContent()['items'][2]['created'],
                    'updated'  => $response->getContent()['items'][2]['updated'],
                    'type'     => NotificationSenderEnum::RABBIT,
                    'name'     => 'Rabbit Test Sender',
                    'class'    => NullRabitHandler::class,
                    'events'   => [],
                    'settings' => [],
                ], [
                    'id'       => $response->getContent()['items'][3]['id'],
                    'created'  => $response->getContent()['items'][3]['created'],
                    'updated'  => $response->getContent()['items'][3]['updated'],
                    'type'     => NotificationSenderEnum::EMAIL,
                    'name'     => 'Hanaboso Email Sender',
                    'class'    => HanabosoNotificationHandler::class,
                    'events'   => [],
                    'settings' => [],
                ],
            ],
        ]);
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
        $response = $this->sendGet(sprintf('/notifications/settings/%s', $response->getContent()['items'][1]['id']));

        $this->assertResponse($response, 200, [
            'id'       => $response->getContent()['id'],
            'created'  => $response->getContent()['created'],
            'updated'  => $response->getContent()['updated'],
            'type'     => NotificationSenderEnum::EMAIL,
            'name'     => 'Email Test Sender',
            'class'    => NullEmailHandler::class,
            'events'   => [],
            'settings' => [],
        ]);
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
        $this->assertResponse($this->sendGet('/notifications/settings/Unknown'), 404, [
            'status'     => 'ERROR',
            'error_code' => 2001,
            'type'       => DocumentNotFoundException::class,
            'message'    => "NotificationSettings with key 'Unknown' not found!",
        ]);
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
        $response = $this->sendPut(sprintf('/notifications/settings/%s', $response->getContent()['items'][1]['id']), [
            NotificationSettings::EVENTS   => [NotificationEventEnum::ACCESS_EXPIRATION],
            NotificationSettings::SETTINGS => [
                EmailDto::EMAILS => [
                    'another-one@example.com', 'another-two@example.com',
                ],
            ],
        ]);

        $this->assertResponse($response, 200, [
            'id'       => $response->getContent()['id'],
            'created'  => $response->getContent()['created'],
            'updated'  => $response->getContent()['updated'],
            'type'     => NotificationSenderEnum::EMAIL,
            'name'     => 'Email Test Sender',
            'class'    => NullEmailHandler::class,
            'events'   => [NotificationEventEnum::ACCESS_EXPIRATION],
            'settings' => [
                EmailDto::EMAILS => [
                    'another-one@example.com', 'another-two@example.com',
                ],
            ],
        ]);
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
        $this->assertResponse($this->sendPut('/notifications/settings/Unknown'), 404, [
            'status'     => 'ERROR',
            'error_code' => 2001,
            'type'       => DocumentNotFoundException::class,
            'message'    => "NotificationSettings with key 'Unknown' not found!",
        ]);
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
        $response = $this->sendPut(sprintf('/notifications/settings/%s', $response->getContent()['items'][1]['id']), [
            NotificationSettings::SETTINGS => [],
        ]);

        $this->assertResponse($response, 404, [
            'status'     => 'ERROR',
            'error_code' => 2001,
            'type'       => NotificationException::class,
            'message'    => "Required settings 'emails' for type 'email' is missing!",
        ]);
    }

}
