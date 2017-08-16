<?php declare(strict_types=1);

namespace Tests\Integration\Commons\Metrics;

use Hanaboso\PipesFramework\Commons\Metrics\UDPSender;
use PHPUnit\Framework\TestCase;

/**
 * Class UDPSenderTest
 *
 * @package Tests\Integration\Commons\Metrics
 */
final class UDPSenderTest extends TestCase
{

    /**
     * @covers UDPSender::send()
     */
    public function testSend(): void
    {
        $sender = new UDPSender('google.com', 1111);
        $socket = $sender->getSocket();
        $result = $sender->send('abc,name=def,host=ghi key1=val1,key2=val2 1465839830100400200');

        $this->assertSame($socket, $sender->getSocket());
        $this->assertTrue($result);
    }

    /**
     * @covers UDPSender::send()
     */
    public function testSendFailed(): void
    {
        $sender = new UDPSender('...', 1111);
        $socket = $sender->getSocket();
        $result = $sender->send('abc,name=def,host=ghi key1=val1,key2=val2 1465839830100400200');

        $this->assertSame($socket, $sender->getSocket());
        $this->assertFalse($result);
    }

}