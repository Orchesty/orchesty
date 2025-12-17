<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\Configurator\Model;

use Hanaboso\PipesFramework\Configurator\Model\DashboardDto;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\DataProvider;
use PipesFrameworkTests\KernelTestCaseAbstract;

/**
 * Class DashboardDtoTest
 *
 * @package PipesFrameworkTests\Unit\Configurator\Model
 */
#[CoversClass(DashboardDto::class)]
final class DashboardDtoTest extends KernelTestCaseAbstract
{

    /**
     * @return void
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
