<?php declare(strict_types=1);

namespace DemoTests\Integration\Connector;

use Closure;
use Demo\Connector\PagerDutyConnector;
use DemoTests\KernelTestCaseAbstract;
use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\Utils\File\File;
use Hanaboso\Utils\String\Json;
use ReflectionException;

/**
 * Class PagerDutyConnectorTest
 *
 * @package DemoTests\Integration\Connector
 * @covers  \Demo\Connector\PagerDutyConnector
 */
final class PagerDutyConnectorTest extends KernelTestCaseAbstract
{

    /**
     * @var PagerDutyConnector
     */
    private PagerDutyConnector $connector;

    /**
     * @covers \Demo\Connector\PagerDutyConnector::getName
     */
    public function testGetName(): void
    {
        self::assertEquals('pager_duty.schedule', $this->connector->getName());
    }

    /**
     * @covers \Demo\Connector\PagerDutyConnector::processAction
     *
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $data = Json::decode(
            $this->prepareService(
                static fn() => new ResponseDto(
                    200,
                    '',
                    File::getContent(__DIR__ . '/data/pagerDuty.json'),
                    [],
                ),
            )->processAction((new ProcessDto())->setData('{}'))->getData(),
        );

        self::assertEquals(40, $data['Radek Jirsa']['hours']);
        self::assertEquals(40, $data['Marcel Pavlíček']['hours']);
        self::assertEquals(40, $data['Tomáš Procházka']['hours']);
        self::assertEquals(45, $data['Václav Krecl']['hours']);
        self::assertEquals(18, $data['Jakub Husák']['hours']);
    }

    /**
     * @covers \Demo\Connector\PagerDutyConnector::processAction
     *
     * @throws Exception
     */
    public function testProcessHourAction(): void
    {
        $data = Json::decode(
            $this->prepareService(
                static fn() => new ResponseDto(
                    200,
                    '',
                    File::getContent(__DIR__ . '/data/pagerDutyHours.json'),
                    [],
                ),
            )->processAction((new ProcessDto())->setData('{}'))->getData(),
        );

        self::assertEquals(64, $data['Radek Jirsa']['hours']);
        self::assertEquals(40, $data['Marcel Pavlíček']['hours']);
        self::assertEquals(40, $data['Tomáš Procházka']['hours']);
        self::assertEquals(45, $data['Václav Krecl']['hours']);
        self::assertEquals(18, $data['Jakub Husák']['hours']);
    }

    /**
     * @covers \Demo\Connector\PagerDutyConnector::processAction
     *
     * @throws Exception
     */
    public function testProcessActionException(): void
    {
        self::expectException(ConnectorException::class);
        self::expectExceptionCode(0);
        self::expectExceptionMessage('Server response with status code [500]');

        $this->prepareService(static fn() => new ResponseDto(500, '', '', []))
            ->processAction((new ProcessDto())->setData('{}'))->getData();
    }

    /**
     * @covers \Demo\Connector\PagerDutyConnector::getComputedHours
     *
     * @throws ReflectionException
     */
    public function testGetComputedHours(): void
    {
        $hours = 5;
        $this->invokeMethod($this->connector, 'getComputedHours', ['2020-01-06', &$hours]);
        self::assertEquals(0, $hours);
    }

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->connector = self::getContainer()->get('hbpf.connector.pager-duty');
    }

    /**
     * @param Closure $closure
     *
     * @return PagerDutyConnector
     * @throws Exception
     */
    private function prepareService(Closure $closure): PagerDutyConnector
    {
        $curl = self::createMock(CurlManagerInterface::class);
        $curl->method('send')->willReturnCallback($closure);

        $repo = self::getContainer()->get('hbpf.application_install.repository');

        $pagerDutyConnector = new PagerDutyConnector($repo);
        $pagerDutyConnector->setSender($curl);

        return $pagerDutyConnector;
    }

}
