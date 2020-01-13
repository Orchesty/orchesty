<?php declare(strict_types=1);

namespace Tests\Unit\HbPFNotificationBundle\Model;

use Exception;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Notification\Exception\NotificationException;
use Hanaboso\PipesFramework\Notification\Model\NotificationManager;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\KernelTestCaseAbstract;

/**
 * Class NotificationManagerTest
 *
 * @package Tests\Unit\HbPFNotificationBundle\Model
 */
final class NotificationManagerTest extends KernelTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testReadSettings(): void
    {
        $this->getManager(
            static function (RequestDto $request): ResponseDto {
                self::assertEquals(CurlManager::METHOD_GET, $request->getMethod());
                self::assertEquals('http://example.com/notifications/settings', $request->getUri(TRUE));

                return new ResponseDto(200, 'OK', '', []);
            }
        )->getSettings();
    }

    /**
     * @throws Exception
     */
    public function testReadSetting(): void
    {
        $this->getManager(
            static function (RequestDto $request): ResponseDto {
                self::assertEquals(CurlManager::METHOD_GET, $request->getMethod());
                self::assertEquals('http://example.com/notifications/settings/id', $request->getUri(TRUE));

                return new ResponseDto(200, 'OK', '', []);
            }
        )->getSetting('id');
    }

    /**
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
            }
        )->updateSettings('id', ['type' => 'Type']);
    }

    /**
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
                    CurlException::REQUEST_FAILED
                );
            }
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
        /** @var CurlManager|MockObject $curlManager */
        $curlManager = self::createPartialMock(CurlManager::class, ['send']);
        $curlManager->method('send')->willReturnCallback($callback);

        return new NotificationManager($curlManager, 'http://example.com/');
    }

}
