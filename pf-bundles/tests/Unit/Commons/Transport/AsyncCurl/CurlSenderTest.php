<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10.10.17
 * Time: 13:52
 */

namespace Tests\Unit\Commons\Transport\AsyncCurl;

use Clue\React\Buzz\Browser;
use Clue\React\Buzz\Message\ResponseException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Metrics\InfluxDbSender;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSender;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Http\Message\ResponseInterface;
use function React\Promise\reject;
use function React\Promise\resolve;

/**
 * Class CurlSenderTest
 *
 * @package Tests\Unit\RabbitMq\Async\Curl
 */
class CurlSenderTest extends TestCase
{

    /**
     * @covers CurlSender::send()
     */
    public function testSend(): void
    {
        /** @var Browser|PHPUnit_Framework_MockObject_MockObject $browser */
        $browser = $this->createMock(Browser::class);
        $browser->method('send')->willReturn(resolve(new Response(201)));

        /** @var InfluxDbSender $influx */
        $influx = $this->createMock(InfluxDbSender::class);

        $curl    = new CurlSender($browser, $influx);
        $request = new RequestDto('GET', new Uri('https://cleverconn.stage.hanaboso.net/api/'));

        $curl
            ->send($request)
            ->then(function (ResponseInterface $response): void {
                $this->assertSame(201, $response->getStatusCode());
            })
            ->done();
    }

    /**
     * @covers CurlSender::send()
     */
    public function testSendException(): void
    {
        /** @var Browser|PHPUnit_Framework_MockObject_MockObject $browser */
        $browser = $this->createMock(Browser::class);
        $browser->method('send')->willReturn(reject(new ResponseException(new Response(401))));

        /** @var InfluxDbSender $influx */
        $influx = $this->createMock(InfluxDbSender::class);

        $curl    = new CurlSender($browser, $influx);
        $request = new RequestDto('GET', new Uri('https://cleverconn.stage.hanaboso.net/api/'));

        $curl
            ->send($request)
            ->then(NULL, function ($e): void {
                $this->assertInstanceOf(ResponseException::class, $e);
            })
            ->done();
    }

}