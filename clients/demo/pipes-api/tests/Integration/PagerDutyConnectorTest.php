<?php declare(strict_types=1);

namespace Tests\Integration;

use Demo\Connector\PagerDutyConnector;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use ReflectionException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class PagerDutyConnectorTest
 *
 * @package Tests\Integration
 */
class PagerDutyConnectorTest extends KernelTestCase
{

    /**
     *
     */
    protected function setUp(): void
    {
        self::bootKernel();
    }

    /**
     * @throws ReflectionException
     * @throws CurlException
     */
    public function testProcessAction(): void
    {
        $dto  = new ResponseDto(200, '', (string) file_get_contents(__DIR__ . '/data.json'), []);
        $curl = self::createMock(CurlManagerInterface::class);

        $curl->method('send')
            ->willReturn($dto);
        /** @var CurlManagerInterface $curl */
        $curl = $curl;
        $connector = new PagerDutyConnector($curl);
        $data      = $connector->processAction(new ProcessDto())->getData();
        $arr       = json_decode($data, TRUE, 512, JSON_THROW_ON_ERROR);
        self::assertEquals(40, $arr['Radek Jirsa']['hours']);
        self::assertEquals(40, $arr['Marcel Pavlíček']['hours']);
        self::assertEquals(40, $arr['Tomáš Procházka']['hours']);
        self::assertEquals(45, $arr['Václav Krecl']['hours']);
        self::assertEquals(42, $arr['Jakub Husák']['hours']);
    }

}