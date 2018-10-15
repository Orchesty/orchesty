<?php declare(strict_types=1);

namespace Tests\Unit\Models;

use CcApi\Curl\CurlSender;
use CleverCore\SocialMultichannel\Enums\AdTypeEnum;
use CleverCore\SocialMultichannel\Models\PipesSender;
use Exception;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use Tests\ControllerTestCaseAbstract;

/**
 * Class PipesSenderTest
 *
 * @package Tests\Unit\Models
 */
final class PipesSenderTest extends ControllerTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testCreateAd(): void
    {
        $this->prepareService(function (Request $request): ResponseInterface {
            $this->assertEquals(CurlSender::POST, $request->getMethod());
            $this->assertEquals(
                'https://example.com/system/fb/user/123/action/createAudience',
                (string) $request->getUri()
            );
            $this->assertEquals([
                'Host'         => ['example.com'],
                'Content-Type' => ['application/json'],
                'Accept'       => ['application/json'],
            ], $request->getHeaders());

            return new Response();
        })->createAd(AdTypeEnum::FB, '123', []);
    }

    /**
     * @throws Exception
     */
    public function testSyncAudience(): void
    {
        $this->prepareService(function (Request $request): ResponseInterface {
            $this->assertEquals(CurlSender::POST, $request->getMethod());
            $this->assertEquals(
                'https://example.com/system/fb/user/123/action/syncAudience',
                (string) $request->getUri()
            );
            $this->assertEquals([
                'Host'         => ['example.com'],
                'Content-Type' => ['application/json'],
                'Accept'       => ['application/json'],
            ], $request->getHeaders());

            return new Response();
        })->syncAudience(AdTypeEnum::FB, '123', []);
    }

    /**
     * @throws Exception
     */
    public function testRemoveMirror(): void
    {
        $this->prepareService(function (Request $request): ResponseInterface {
            $this->assertEquals(CurlSender::POST, $request->getMethod());
            $this->assertEquals(
                'https://example.com/system/fb/user/123/action/deleteAd',
                (string) $request->getUri()
            );
            $this->assertEquals([
                'Host'         => ['example.com'],
                'Content-Type' => ['application/json'],
                'Accept'       => ['application/json'],
            ], $request->getHeaders());

            return new Response();
        })->removeMirror(AdTypeEnum::FB, '123', []);
    }

    /**
     * @param callable $callback
     *
     * @return PipesSender
     * @throws Exception
     */
    private function prepareService(callable $callback): PipesSender
    {
        /** @var CurlSender|MockObject $curlSender */
        $curlSender = $this->createPartialMock(CurlSender::class, ['send']);
        $curlSender->method('send')->willReturnCallback($callback);

        return new PipesSender('https://example.com', $curlSender);
    }

}