<?php declare(strict_types=1);

namespace NotificationSenderTests\Controller;

use Exception;
use Hanaboso\NotificationSender\Handler\NotificationSettingsHandler;
use NotificationSenderTests\ControllerTestCaseAbstract;

/**
 * Class NotificationSettingsControllerTest
 *
 * @package NotificationSenderTests\Controller
 *
 * @covers  \Hanaboso\NotificationSender\Controller\NotificationSettingsController
 * @covers  \Hanaboso\NotificationSender\Handler\NotificationSettingsHandler
 */
final class NotificationSettingsControllerTest extends ControllerTestCaseAbstract
{

    private const ID    = 'id';
    private const BODY  = 'body';
    private const ITEMS = 'items';

    /**
     * @covers \Hanaboso\NotificationSender\Controller\NotificationSettingsController::listSettingsAction
     * @covers \Hanaboso\NotificationSender\Handler\NotificationSettingsHandler::listSettings
     *
     * @throws Exception
     */
    public function testListSettings(): void
    {
        $this->assertResponse(
            __DIR__ . '/data/NotificationSettingsControllerTest/listSettingsRequest.json',
            ['id' => '123456789', 'created' => '2010-10-10 10:10:10', 'updated' => '2010-10-10 10:10:10']
        );
    }

    /**
     * @covers \Hanaboso\NotificationSender\Controller\NotificationSettingsController::listSettingsAction
     * @covers \Hanaboso\NotificationSender\Handler\NotificationSettingsHandler::listSettings
     *
     * @throws Exception
     */
    public function testListSettingsException(): void
    {
        $this->prepareHandler('listSettings');

        $this->assertResponse(__DIR__ . '/data/NotificationSettingsControllerTest/listSettingsExceptionRequest.json');
    }

    /**
     * @covers \Hanaboso\NotificationSender\Controller\NotificationSettingsController::getSettingsAction
     * @covers \Hanaboso\NotificationSender\Handler\NotificationSettingsHandler::getSettings
     *
     * @throws Exception
     */
    public function testGetSettings(): void
    {
        $this->getSettingsId();

        $this->assertResponse(
            __DIR__ . '/data/NotificationSettingsControllerTest/getSettingsRequest.json',
            ['id' => '123456789', 'created' => '2010-10-10 10:10:10', 'updated' => '2010-10-10 10:10:10'],
            [':id' => $this->getSettingsId()]
        );
    }

    /**
     * @covers \Hanaboso\NotificationSender\Controller\NotificationSettingsController::getSettingsAction
     * @covers \Hanaboso\NotificationSender\Handler\NotificationSettingsHandler::getSettings
     *
     * @throws Exception
     */
    public function testGetSettingsNotFound(): void
    {
        $this->assertResponse(__DIR__ . '/data/NotificationSettingsControllerTest/getSettingsNotFoundRequest.json');
    }

    /**
     * @covers \Hanaboso\NotificationSender\Controller\NotificationSettingsController::getSettingsAction
     * @covers \Hanaboso\NotificationSender\Handler\NotificationSettingsHandler::getSettings
     *
     * @throws Exception
     */
    public function testGetSettingsException(): void
    {
        $this->prepareHandler('getSettings');

        $this->assertResponse(__DIR__ . '/data/NotificationSettingsControllerTest/getSettingsExceptionRequest.json');
    }

    /**
     * @covers \Hanaboso\NotificationSender\Controller\NotificationSettingsController::saveSettingsAction
     * @covers \Hanaboso\NotificationSender\Handler\NotificationSettingsHandler::saveSettings
     *
     * @throws Exception
     */
    public function testSaveSettings(): void
    {
        $this->getSettingsId();

        $this->assertResponse(
            __DIR__ . '/data/NotificationSettingsControllerTest/saveSettingsRequest.json',
            ['id' => '123456789', 'created' => '2010-10-10 10:10:10', 'updated' => '2010-10-10 10:10:10'],
            [':id' => $this->getSettingsId()]
        );
    }

    /**
     * @covers \Hanaboso\NotificationSender\Controller\NotificationSettingsController::saveSettingsAction
     * @covers \Hanaboso\NotificationSender\Handler\NotificationSettingsHandler::saveSettings
     *
     * @throws Exception
     */
    public function testSaveSettingsNotFound(): void
    {
        $this->assertResponse(__DIR__ . '/data/NotificationSettingsControllerTest/saveSettingsNotFoundRequest.json');
    }

    /**
     * @covers \Hanaboso\NotificationSender\Controller\NotificationSettingsController::saveSettingsAction
     * @covers \Hanaboso\NotificationSender\Handler\NotificationSettingsHandler::saveSettings
     *
     * @throws Exception
     */
    public function testSaveSettingsNotFoundRequired(): void
    {
        $this->getSettingsId();

        $this->assertResponse(
            __DIR__ . '/data/NotificationSettingsControllerTest/saveSettingsNotFoundRequiredRequest.json',
            [],
            [':id' => $this->getSettingsId()]
        );
    }

    /**
     * @covers \Hanaboso\NotificationSender\Controller\NotificationSettingsController::saveSettingsAction
     * @covers \Hanaboso\NotificationSender\Handler\NotificationSettingsHandler::saveSettings
     *
     * @throws Exception
     */
    public function testSaveSettingsException(): void
    {
        $this->prepareHandler('saveSettings');

        $this->assertResponse(__DIR__ . '/data/NotificationSettingsControllerTest/saveSettingsExceptionRequest.json');
    }

    /**
     * @return string
     *
     * @throws Exception
     */
    private function getSettingsId(): string
    {
        return $this->sendRequest('GET', '/notifications/settings')[self::BODY][self::ITEMS][1][self::ID];
    }

    /**
     * @param string $method
     */
    private function prepareHandler(string $method): void
    {
        $handler = self::createMock(NotificationSettingsHandler::class);
        $handler->method($method)->willThrowException(new Exception('Something gone wrong!'));

        self::$container->set('notification.handler.settings', $handler);
    }

}
