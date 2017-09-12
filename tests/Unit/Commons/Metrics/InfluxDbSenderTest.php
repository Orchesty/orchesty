<?php declare(strict_types=1);

namespace Tests\Unit\Commons\Metrics;

use Hanaboso\PipesFramework\Commons\Metrics\InfluxDbSender;
use Hanaboso\PipesFramework\Commons\Metrics\UDPSender;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;

/**
 * Class UDPServiceTest
 *
 * @package Tests\Unit\Commons\Metrics
 */
final class InfluxDbSenderTest extends TestCase
{

    /**
     * @covers InfluxDbSender::send()
     * @covers InfluxDbSender::createMessage()
     * @covers InfluxDbSender::join()
     * @covers InfluxDbSender::prepareTags()
     * @covers InfluxDbSender::prepareFields()
     * @covers InfluxDbSender::escapeFieldValue()
     */
    public function testSend(): void
    {

        /** @var PHPUnit_Framework_MockObject_MockObject|UDPSender $sender */
        $sender = $this->createPartialMock(UDPSender::class, ['send']);
        $sender->method('send')->willReturn(TRUE);

        $fields = ['key1' => 'val1', 'key2' => 'val2'];

        $service = new InfluxDbSender($sender, 'php_worker', ['name' => 'localhost']);
        $result  = $service->send($fields);

        $expectedMessage = 'php_worker,name=localhost,host=localhost key1="val1",key2=1,key3=true,key4="null" ';

        $message = preg_replace(
            '/host=[A-Za-z0-9]* /',
            'host=localhost ',
            $service->createMessage(['key1' => 'val1', 'key2' => 1, 'key3' => TRUE, 'key4' => NULL])
        );

        $this->assertTrue($result);
        $this->assertStringStartsWith($expectedMessage, $message);
        $this->assertEquals(
            strlen($expectedMessage) + 19,
            strlen($message)
        );
    }

    /**
     * @covers InfluxDbSender::createMessage()
     */
    public function testCreateMessageException(): void
    {
        /** @var PHPUnit_Framework_MockObject_MockObject|UDPSender $sender */
        $sender = $this->createPartialMock(UDPSender::class, ['send']);
        $sender->method('send')->willReturn(TRUE);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The fields must not be empty.');
        $service = new InfluxDbSender($sender, 'php_worker', ['name' => 'localhost']);
        $service->createMessage([]);
    }

}