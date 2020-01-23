<?php declare(strict_types=1);

namespace Tests\Integration;

use Demo\Connector\PagerDutyConnector;
use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\Utils\String\Json;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class PagerDutyConnectorTest
 *
 * @package Tests\Integration
 */
final class PagerDutyConnectorTest extends KernelTestCase
{

    /**
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $dto = new ResponseDto(200, '', (string) file_get_contents(__DIR__ . '/data.json'), []);
        /** @var CurlManagerInterface|MockObject $curl */
        $curl = self::createMock(CurlManagerInterface::class);
        $curl->method('send')
            ->willReturn($dto);

        $connector = new PagerDutyConnector($curl);
        $data      = $connector->processAction(new ProcessDto())->getData();
        $arr       = Json::decode($data);
        self::assertEquals(40, $arr['Radek Jirsa']['hours']);
        self::assertEquals(40, $arr['Marcel Pavlíček']['hours']);
        self::assertEquals(40, $arr['Tomáš Procházka']['hours']);
        self::assertEquals(45, $arr['Václav Krecl']['hours']);
        self::assertEquals(18, $arr['Jakub Husák']['hours']);
    }

    /**
     *
     */
    protected function setUp(): void
    {
        self::bootKernel();
    }

}
