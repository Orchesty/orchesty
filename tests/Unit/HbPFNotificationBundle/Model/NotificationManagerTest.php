<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\HbPFNotificationBundle\Model;

use Exception;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Notification\Exception\NotificationException;
use Hanaboso\PipesFramework\Notification\Model\NotificationManager;
use Monolog\Logger;
use PipesFrameworkTests\KernelTestCaseAbstract;

/**
 * Class NotificationManagerTest
 *
 * @package PipesFrameworkTests\Unit\HbPFNotificationBundle\Model
 */
final class NotificationManagerTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\Notification\Model\NotificationManager
     * @covers \Hanaboso\PipesFramework\Notification\Model\NotificationManager::setLogger
     * @covers \Hanaboso\PipesFramework\Notification\Model\NotificationManager::getSettings
     * @covers \Hanaboso\PipesFramework\Notification\Model\NotificationManager::getUrl
     * @covers \Hanaboso\PipesFramework\Notification\Model\NotificationManager::sendAndProcessRequest
     *
     * @throws Exception
     */
    public function testReadSettings(): void
    {
        $manager = $this->getManager(
            static function (RequestDto $request): ResponseDto {
                self::assertEquals(CurlManager::METHOD_GET, $request->getMethod());
                self::assertEquals('http://example.com/notifications/settings', $request->getUri(TRUE));

                return new ResponseDto(200, 'OK', '', []);
            },
        );

        $manager->setLogger(new Logger('logger'));
        $manager->getSettings();
    }

    /**
     * @covers \Hanaboso\PipesFramework\Notification\Model\NotificationManager::getSetting
     * @covers \Hanaboso\PipesFramework\Notification\Model\NotificationManager::getUrl
     * @covers \Hanaboso\PipesFramework\Notification\Model\NotificationManager::sendAndProcessRequest
     *
     * @throws Exception
     */
    public function testReadSetting(): void
    {
        $this->getManager(
            static function (RequestDto $request): ResponseDto {
                self::assertEquals(CurlManager::METHOD_GET, $request->getMethod());
                self::assertEquals('http://example.com/notifications/settings/id', $request->getUri(TRUE));

                return new ResponseDto(200, 'OK', '', []);
            },
        )->getSetting('id');
    }

    /**
     * @covers \Hanaboso\PipesFramework\Notification\Model\NotificationManager::updateSettings
     * @covers \Hanaboso\PipesFramework\Notification\Model\NotificationManager::getUrl
     * @covers \Hanaboso\PipesFramework\Notification\Model\NotificationManager::sendAndProcessRequest
     *
     * @throws Exception
     */
    public function testUpdateSettings(): void
    {
        $this->getManager(
            static function (RequestDto $request): ResponseDto {
                self::assertEquals(CurlManager::METHOD_PUT, $request->getMethod());
                self::assertEquals('http://example.com/notifications/settings/id', $request->getUri(TRUE));
                self::assertEquals('{"type":"Type"}', $request->getBody());

                return new ResponseDto(200, 'OK', '', []);
            },
        )->updateSettings('id', ['type' => 'Type']);
    }

    /**
     * @covers \Hanaboso\PipesFramework\Notification\Model\NotificationManager::getSettings
     * @covers \Hanaboso\PipesFramework\Notification\Model\NotificationManager::getUrl
     * @covers \Hanaboso\PipesFramework\Notification\Model\NotificationManager::sendAndProcessRequest
     *
     * @throws Exception
     */
    public function testRequestFail(): void
    {
        self::expectException(NotificationException::class);
        self::expectExceptionCode(NotificationException::NOTIFICATION_EXCEPTION);
        self::expectExceptionMessageMatches('#Notification API failed: .+#');

        $this->getManager(
            static function (): void {
                throw new NotificationException(
                    'Client error: `GET http://example.com/notification_settings` resulted in a `404 Not Found` response: Response',
                    CurlException::REQUEST_FAILED,
                );
            },
        )->getSettings();
    }

    /**
     * @param callable $callback
     *
     * @return NotificationManager
     * @throws Exception
     */
    private function getManager(callable $callback): NotificationManager
    {
        $curlManager = self::createPartialMock(CurlManager::class, ['send']);
        $curlManager->method('send')->willReturnCallback($callback);

        return new NotificationManager($curlManager, 'http://example.com/');
    }

}
