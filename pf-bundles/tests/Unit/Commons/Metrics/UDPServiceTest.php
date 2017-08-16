<?php declare(strict_types=1);

namespace Tests\Unit\Commons\Metrics;

use Hanaboso\PipesFramework\Commons\Metrics\UDPSender;
use Hanaboso\PipesFramework\Commons\Metrics\UDPService;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;

/**
 * Class UDPServiceTest
 *
 * @package Tests\Unit\Commons\Metrics
 */
final class UDPServiceTest extends TestCase
{

    /**
     * @covers UDPService::send()
     */
    public function testSend(): void
    {
        /** @var PHPUnit_Framework_MockObject_MockObject|UDPSender $sender */
        $sender = $this->createPartialMock(UDPSender::class, ['send']);
        $sender->method('send')->willReturn(TRUE);

        $fields = ['key1' => 'val1', 'key2' => 'val2'];

        $service = new UDPService($sender);
        $result  = $service->send('localhost', $fields);

        $expectedMessage = 'php-worker,name=localhost,host=localhost key1=val1,key2=val2 ';

        $this->assertTrue($result);
        $this->assertStringStartsWith($expectedMessage, $service->getMessage());
        $this->assertEquals(strlen($expectedMessage) + 19, strlen($service->getMessage()));
    }

}