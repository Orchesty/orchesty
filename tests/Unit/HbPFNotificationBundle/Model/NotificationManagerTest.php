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
        $this->getManager(function (RequestDto $request): ResponseDto {
            $this->assertEquals(CurlManager::METHOD_GET, $request->getMethod());
            $this->assertEquals('http://example.com/notification_settings', $request->getUri(TRUE));

            return new ResponseDto(200, 'OK', '', []);
        })->getSettings();
    }

    /**
     * @throws Exception
     */
    public function testUpdateSettings(): void
    {
        $this->getManager(function (RequestDto $request): ResponseDto {
            $this->assertEquals(CurlManager::METHOD_PUT, $request->getMethod());
            $this->assertEquals('http://example.com/notification_settings', $request->getUri(TRUE));
            $this->assertEquals('{"type":"Type"}', $request->getBody());

            return new ResponseDto(200, 'OK', '', []);
        })->updateSettings(['type' => 'Type']);
    }

    /**
     * @throws Exception
     */
    public function testRequestFail(): void
    {
        $this->expectException(NotificationException::class);
        $this->expectExceptionCode(NotificationException::NOTIFICATION_EXCEPTION);
        $this->expectExceptionMessageRegExp('#Notification API failed: .+#');

        $this->getManager(function (RequestDto $request): void {
            $request;

            throw new NotificationException(
                'Client error: `GET http://example.com/notification_settings` resulted in a `404 Not Found` response: Response',
                CurlException::REQUEST_FAILED
            );
        })->getSettings();
    }

    /**
     * @param callable $callback
     *
     * @return NotificationManager
     */
    private function getManager(callable $callback): NotificationManager
    {
        /** @var CurlManager|MockObject $curlManager */
        $curlManager = $this->createPartialMock(CurlManager::class, ['send']);
        $curlManager->method('send')->willReturnCallback($callback);

        return new NotificationManager($curlManager, 'http://example.com/');
    }

}