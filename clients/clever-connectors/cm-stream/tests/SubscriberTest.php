<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/23/17
 * Time: 10:29 AM
 */

namespace Tests;

use CmStream\Exception\SubscriberException;
use CmStream\GuzzleClientFactory;
use CmStream\Subscriber;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;

/**
 * Class SubscriberTest
 *
 * @package Tests
 */
class SubscriberTest extends TestCase
{

    /**
     * @covers Subscriber::subscribe()
     */
    public function testSubscribe(): void
    {
        $client = $this->createMock(Client::class);
        $client->method('send')->willReturnCallback(function (Request $request) {
            $this->assertSame('/login', $request->getUri()->getPath());
            $this->assertSame('application/json', $request->getHeader('content-type')[0]);
            $this->assertSame(
                ['userId' => '123', 'groups' => []],
                json_decode($request->getBody()->getContents(), TRUE)
            );

            return new Response(
                200,
                ['content-type' => 'application/json'],
                json_encode(['userId' => '123', 'token' => '456'])
            );
        });

        /** @var GuzzleClientFactory|PHPUnit_Framework_MockObject_MockObject $guzzleClientFactory */
        $guzzleClientFactory = $this->createMock(GuzzleClientFactory::class);
        $guzzleClientFactory->method('create')->willReturn($client);

        $subscriber = new Subscriber($guzzleClientFactory);

        $this->assertSame('456', $subscriber->subscribe('123'));
    }

    /**
     * @covers Subscriber::subscribe()
     */
    public function testSubscribeNoToken(): void
    {
        $client = $this->createMock(Client::class);
        $client->method('send')->willReturn(new Response());

        /** @var GuzzleClientFactory|PHPUnit_Framework_MockObject_MockObject $guzzleClientFactory */
        $guzzleClientFactory = $this->createMock(GuzzleClientFactory::class);
        $guzzleClientFactory->method('create')->willReturn($client);

        $subscriber = new Subscriber($guzzleClientFactory);

        $this->expectException(SubscriberException::class);
        $this->expectExceptionMessage('Token is empty.');
        $subscriber->subscribe('123');
    }

    /**
     * @covers Subscriber::subscribe()
     */
    public function testSubscribeException(): void
    {
        $client = $this->createMock(Client::class);
        $client->method('send')->willThrowException(new Exception('Subscribe error'));

        /** @var GuzzleClientFactory|PHPUnit_Framework_MockObject_MockObject $guzzleClientFactory */
        $guzzleClientFactory = $this->createMock(GuzzleClientFactory::class);
        $guzzleClientFactory->method('create')->willReturn($client);

        $subscriber = new Subscriber($guzzleClientFactory);

        $this->expectException(SubscriberException::class);
        $this->expectExceptionMessage('Curl sender error: Subscribe error');
        $subscriber->subscribe('123');
    }

    /**
     * @covers Subscriber::unsubscribe()
     */
    public function testUnsubscribe(): void
    {
        $client = $this->createMock(Client::class);
        $client->method('send')->willReturnCallback(function (Request $request) {
            $this->assertSame('/logout', $request->getUri()->getPath());
            $this->assertSame('application/json', $request->getHeader('content-type')[0]);
            $this->assertSame(
                ['token' => '123'],
                json_decode($request->getBody()->getContents(), TRUE)
            );

            return new Response(
                200,
                ['content-type' => 'application/json'],
                json_encode(['userId' => '123'])
            );
        });

        /** @var GuzzleClientFactory|PHPUnit_Framework_MockObject_MockObject $guzzleClientFactory */
        $guzzleClientFactory = $this->createMock(GuzzleClientFactory::class);
        $guzzleClientFactory->method('create')->willReturn($client);

        $subscriber = new Subscriber($guzzleClientFactory);

        $subscriber->unsubscribe('123');
    }

    /**
     * @covers Subscriber::unsubscribe()
     */
    public function testUnsubscribeException(): void
    {
        $client = $this->createMock(Client::class);
        $client->method('send')->willThrowException(new Exception('Unsubscribe error'));

        /** @var GuzzleClientFactory|PHPUnit_Framework_MockObject_MockObject $guzzleClientFactory */
        $guzzleClientFactory = $this->createMock(GuzzleClientFactory::class);
        $guzzleClientFactory->method('create')->willReturn($client);

        $subscriber = new Subscriber($guzzleClientFactory);

        $this->expectException(SubscriberException::class);
        $this->expectExceptionMessage('Curl sender error: Unsubscribe error');
        $subscriber->unsubscribe('123');
    }

}