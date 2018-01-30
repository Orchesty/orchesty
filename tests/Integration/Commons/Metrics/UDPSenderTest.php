<?php declare(strict_types=1);

namespace Tests\Integration\Commons\Metrics;

use DateTime;
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
        $message = 'abc,name=def,host=ghi key1=val1,key2=val2 1465839830100400200';


        $sender = new UDPSender('invalidHost', 61999);
        $result = $sender->send($message);
        $this->assertFalse($result);

        $sender = new UDPSender('localhost', 61999);
        $result = $sender->send($message);
        $this->assertTrue($result);

//        $start = (new DateTime())->getTimestamp();
//        $end = (new DateTime())->getTimestamp();
//        $this->assertGreaterThanOrEqual($end, $start + 1);
    }

//    private function createServer()
//    {
//        //Create a UDP socket
//        $sock = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
//        $this->assertNotEquals(FALSE, $sock, "Failed creating socket.");
//
//        $bind = @socket_bind($sock, "0.0.0.0", 61999);
//        $this->assertEquals(TRUE, $bind);
//
//        $this->serverOn = TRUE;
//
//        while ($this->serverOn) {
//            socket_recvfrom($sock, $buf, 512, 0, $remote_ip, $remote_port);
//            $this->receivedPacket = $buf;
//        }
//
//        socket_close($sock);
//    }

}