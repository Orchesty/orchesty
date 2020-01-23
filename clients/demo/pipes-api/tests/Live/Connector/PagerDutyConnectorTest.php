<?php declare(strict_types=1);

namespace Tests\Live\Connector;

use Demo\Connector\PagerDutyConnector;
use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\Utils\String\Json;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class PagerDutyConnectorTest
 *
 * @package Tests\Live\Connector
 */
final class PagerDutyConnectorTest extends KernelTestCase
{

    /**
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $manager   = self::$container->get('hbpf.transport.curl_manager');
        $connector = new PagerDutyConnector($manager);
        $dto       = new ProcessDto();
        $dto->setData(Json::encode(['since' => '2019-04-19', 'until' => '2019-04-29']));
        $data = $connector->processAction($dto)->getData();
        $arr  = Json::decode($data);
        self::assertEquals(56, $arr['Radek Jirsa']['hours']);
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
