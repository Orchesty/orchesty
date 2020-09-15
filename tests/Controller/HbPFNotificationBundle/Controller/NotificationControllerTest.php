<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFNotificationBundle\Controller;

use Exception;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use PipesFrameworkTests\ControllerTestCaseAbstract;
use Throwable;

/**
 * Class NotificationControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFNotificationBundle\Controller
 */
final class NotificationControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\HbPFNotificationBundle\Controller\NotificationController
     * @covers \Hanaboso\PipesFramework\HbPFNotificationBundle\Controller\NotificationController::getSettingAction
     * @covers \Hanaboso\PipesFramework\HbPFNotificationBundle\Handler\NotificationHandler
     * @covers \Hanaboso\PipesFramework\HbPFNotificationBundle\Handler\NotificationHandler::getSettings
     *
     * @throws Exception
     */
    public function testGetSettingsAction(): void
    {
        $this->mockSender(new ResponseDto(200, 'notification', '{"notification":"settings"}', []));

        $this->assertResponse(__DIR__ . '/data/getSettingsRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFNotificationBundle\Controller\NotificationController::getSettingsAction
     * @covers \Hanaboso\PipesFramework\HbPFNotificationBundle\Handler\NotificationHandler::getSettings
     *
     * @throws Exception
     */
    public function testGetSettingsActionErr(): void
    {
        $this->mockSenderException(new CurlException());

        $this->assertResponse(__DIR__ . '/data/getSettingsErrRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFNotificationBundle\Controller\NotificationController::getSettingEventsAction
     *
     * @throws Exception
     */
    public function testGetSettingsEventAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/getSettingsEventsRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFNotificationBundle\Controller\NotificationController::getSettingAction
     * @covers \Hanaboso\PipesFramework\HbPFNotificationBundle\Handler\NotificationHandler::getSetting
     *
     * @throws Exception
     */
    public function testGetSettingAction(): void
    {
        $this->mockSender(new ResponseDto(200, 'notification', '[{"foo":"bar"}]', []));

        $this->assertResponse(__DIR__ . '/data/getSettingRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFNotificationBundle\Controller\NotificationController::getSettingAction
     * @covers \Hanaboso\PipesFramework\HbPFNotificationBundle\Handler\NotificationHandler::getSetting
     *
     * @throws Exception
     */
    public function testGetSettingErrAction(): void
    {
        $this->mockSenderException(new CurlException());

        $this->assertResponse(__DIR__ . '/data/getSettingErrRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFNotificationBundle\Controller\NotificationController::updateSettingsAction
     * @covers \Hanaboso\PipesFramework\HbPFNotificationBundle\Handler\NotificationHandler::updateSettings
     *
     * @throws Exception
     */
    public function testUpdateSettingsAction(): void
    {
        $this->mockSender(new ResponseDto(200, 'notification', '[{"foo":"bar"}]', []));

        $this->assertResponse(__DIR__ . '/data/updateSettingsRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFNotificationBundle\Controller\NotificationController::updateSettingsAction
     * @covers \Hanaboso\PipesFramework\HbPFNotificationBundle\Handler\NotificationHandler::updateSettings
     *
     * @throws Exception
     */
    public function testUpdateSettingsActionErr(): void
    {
        $this->mockSenderException(new CurlException());

        $this->assertResponse(__DIR__ . '/data/updateSettingsErrRequest.json');
    }

    /**
     * @param ResponseDto $dto
     */
    private function mockSender(ResponseDto $dto): void
    {
        $sender = self::createPartialMock(CurlManager::class, ['send']);
        $sender->expects(self::any())->method('send')->willReturn($dto);
        self::$container->set('hbpf.transport.curl_manager', $sender);
    }

    /**
     * @param Throwable $t
     */
    private function mockSenderException(Throwable $t): void
    {
        $sender = self::createPartialMock(CurlManager::class, ['send']);
        $sender->expects(self::any())->method('send')->willThrowException($t);
        self::$container->set('hbpf.transport.curl_manager', $sender);
    }

}
