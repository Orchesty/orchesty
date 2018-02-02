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

    private const LIMIT = 1;

    /**
     * Test whether resolving ip address returns the ip address string or empty string if cannot be resolved.
     * Also tests if the resolving of invalid host does not take too long.
     *
     * @covers UDPSender::refreshIp()
     */
    public function testRefreshIp(): void
    {
        $start = microtime(TRUE);

        $sender = new UDPSender('localhost', 61999);
        $ip     = $sender->refreshIp();
        $this->assertEquals('127.0.0.1', $ip);

        $ip = $sender->refreshIp();
        $this->assertEquals('127.0.0.1', $ip);

        $sender = new UDPSender('google.com', 61999);
        $ip     = $sender->refreshIp();
        $this->assertCount(4, explode(".", $ip));

        $sender = new UDPSender('invalidhostname', 61999);
        $ip     = $sender->refreshIp();
        $this->assertEquals('', $ip);

        $end = microtime(TRUE);
        $this->assertLessThanOrEqual(self::LIMIT, $end - $start);
    }

    /**
     * @covers UDPSender::send()
     */
    public function testSend(): void
    {
        $start = microtime(TRUE);

        $message = 'abc,name=def,host=ghi key1=val1,key2=val2 1465839830100400200';

        $sender = new UDPSender('localhost', 61999);
        $result = $sender->send($message);
        $this->assertTrue($result);

        $sender = new UDPSender('invalidhost', 61999);
        $result = $sender->send($message);
        $this->assertFalse($result);

        // here we cannot assert result because we don't know if influxdb host exists
        // but we can check if packets are delivered right in influxdb container using tcpdump or similar tool
        $sender = new UDPSender('influxdb', 61999);
        $sender->send($message);

        // Check if sending is not delaying too much
        $end = microtime(TRUE);
        $this->assertLessThanOrEqual(self::LIMIT, $end - $start);
    }

    /**
     * @covers UDPSender::send()
     */
    public function testSendManyOnNonExistingHost(): void
    {
        $start = microtime(TRUE);

        $message = 'abc,name=def,host=ghi key1=val1,key2=val2 1465839830100400200';
        $sender  = new UDPSender('invalidhost', 61999);

        for ($i = 0; $i < 1000; $i++) {
            $result = $sender->send($message);
            $this->assertFalse($result);
        }

        // Check if sending is not delaying too much
        $end = microtime(TRUE);
        $this->assertLessThanOrEqual(self::LIMIT, $end - $start);
    }

}
