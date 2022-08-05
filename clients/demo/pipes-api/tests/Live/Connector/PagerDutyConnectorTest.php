<?php declare(strict_types=1);

namespace DemoTests\Live\Connector;

use Demo\Connector\PagerDutyConnector;
use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\Utils\String\Json;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class PagerDutyConnectorTest
 *
 * @package DemoTests\Live\Connector
 */
final class PagerDutyConnectorTest extends KernelTestCase
{

    /**
     * @group live
     *
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $sender    = self::getContainer()->get('hbpf.transport.curl_manager');
        $connector = new PagerDutyConnector();
        $connector->setSender($sender);
        $dto = new ProcessDto();
        $dto->setData(Json::encode(['since' => '2020-06-01', 'until' => '2020-06-24']));
        $data = $connector->processAction($dto)->getData();
        $arr  = Json::decode($data);
        self::assertEquals(86, $arr['Radek Jirsa']['hours']);
        self::assertEquals(55, $arr['Marcel Pavlíček']['hours']);
        self::assertEquals(40, $arr['Tomáš Procházka']['hours']);
        self::assertEquals(45, $arr['Václav Krecl']['hours']);
        self::assertEquals(42, $arr['Jakub Husák']['hours']);
    }

    /**
     *
     */
    protected function setUp(): void
    {
        self::bootKernel();
    }

}
