<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10.10.17
 * Time: 13:52
 */

namespace Tests\Unit\RabbitMq\Async\Curl;

use Clue\React\Buzz\Browser;
use Clue\React\Buzz\Message\ResponseException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Hanaboso\PipesFramework\RabbitMq\Async\Curl\CurlSender;
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

        $curl = new CurlSender($browser);

        $request = new Request('GET', 'https://cleverconn.stage.hanaboso.net/api/');

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

        $curl = new CurlSender($browser);

        $request = new Request('GET', 'https://cleverconn.stage.hanaboso.net/api/');

        $curl
            ->send($request)
            ->then(NULL, function ($e): void {
                $this->assertInstanceOf(ResponseException::class, $e);
            })
            ->done();
    }

}