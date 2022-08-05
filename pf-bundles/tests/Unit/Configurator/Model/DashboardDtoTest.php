<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\Configurator\Model;

use Hanaboso\PipesFramework\Configurator\Model\DashboardDto;
use PipesFrameworkTests\DataProvider;
use PipesFrameworkTests\KernelTestCaseAbstract;

/**
 * Class DashboardDtoTest
 *
 * @package PipesFrameworkTests\Unit\Configurator\Model
 */
final class DashboardDtoTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\DashboardDto::setActiveTopologies
     * @covers \Hanaboso\PipesFramework\Configurator\Model\DashboardDto::setDisabledTopologies
     * @covers \Hanaboso\PipesFramework\Configurator\Model\DashboardDto::setTotalRuns
     * @covers \Hanaboso\PipesFramework\Configurator\Model\DashboardDto::setErrorsCount
     * @covers \Hanaboso\PipesFramework\Configurator\Model\DashboardDto::setSuccessCount
     * @covers \Hanaboso\PipesFramework\Configurator\Model\DashboardDto::setCpu
     * @covers \Hanaboso\PipesFramework\Configurator\Model\DashboardDto::setMemory
     * @covers \Hanaboso\PipesFramework\Configurator\Model\DashboardDto::setSpace
     * @covers \Hanaboso\PipesFramework\Configurator\Model\DashboardDto::setTcpConnections
     * @covers \Hanaboso\PipesFramework\Configurator\Model\DashboardDto::setRange
     * @covers \Hanaboso\PipesFramework\Configurator\Model\DashboardDto::setErrorLogs
     * @covers \Hanaboso\PipesFramework\Configurator\Model\DashboardDto::setAlertLogs
     * @covers \Hanaboso\PipesFramework\Configurator\Model\DashboardDto::addAlertLog
     * @covers \Hanaboso\PipesFramework\Configurator\Model\DashboardDto::addErrorLog
     */
    public function testDto(): void
    {
        $dto = (new DashboardDto())
            ->setActiveTopologies(1)
            ->setDisabledTopologies(2)
            ->setInstalledApps(1)
            ->setTotalRuns(3)
            ->setErrorsCount(4)
            ->setSuccessCount(5)
            ->setCpu(6.1)
            ->setMemory(6.2)
            ->setSpace(7.2)
            ->setTcpConnections(8)
            ->setRange('24h')
            ->setErrorLogs(DataProvider::dashboardLogs(2, TRUE))
            ->setAlertLogs(DataProvider::dashboardLogs(3));

        self::assertEquals($dto->toArray(), $dto->toArray());
    }

}
