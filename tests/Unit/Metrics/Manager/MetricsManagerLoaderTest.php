<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\Metrics\Manager;

use Exception;
use Hanaboso\PipesFramework\Metrics\Manager\MetricsManagerAbstract;
use Hanaboso\PipesFramework\Metrics\Manager\MetricsManagerLoader;
use Hanaboso\PipesPhpSdk\Database\Document\Topology;
use LogicException;
use PipesFrameworkTests\KernelTestCaseAbstract;

/**
 * Class MetricsManagerLoaderTest
 *
 * @package PipesFrameworkTests\Unit\Metrics\Manager
 */
final class MetricsManagerLoaderTest extends KernelTestCaseAbstract
{

    /**
     * @dataProvider loaderDataProvider
     *
     * @covers       \Hanaboso\PipesFramework\Metrics\Manager\MetricsManagerLoader
     * @covers       \Hanaboso\PipesFramework\Metrics\Manager\MetricsManagerLoader::getManager
     * @covers       \Hanaboso\PipesFramework\Metrics\Manager\MetricsManagerAbstract::getTopologyMetrics
     *
     * @param MetricsManagerLoader $loader
     * @param string               $service
     * @param string|null          $err
     *
     * @throws Exception
     */
    public function testGetManager(MetricsManagerLoader $loader, string $service, ?string $err): void
    {
        new MetricsManagerLoader(
            'mongo',
            self::createMock(MetricsManagerAbstract::class),
            self::createMock(MetricsManagerAbstract::class),
        );

        if ($err) {
            self::expectException(LogicException::class);
            self::expectExceptionMessage($err);
        }
        $topo = self::createMock(Topology::class);

        self::assertEquals($service, $loader->getManager()->getTopologyMetrics($topo, [])[0]);
    }

    /**
     * @return mixed[]
     */
    public function loaderDataProvider(): array
    {
        $influx = self::createMock(MetricsManagerAbstract::class);
        $influx->method('getTopologyMetrics')->willReturn(['influx']);
        $mongo = self::createMock(MetricsManagerAbstract::class);
        $mongo->method('getTopologyMetrics')->willReturn(['mongo']);

        return [
            [new MetricsManagerLoader('mongo', $influx, $mongo), 'mongo', NULL],
            [new MetricsManagerLoader('influx', $influx, $mongo), 'influx', NULL],
            [new MetricsManagerLoader('asd', $influx, $mongo), '', '[asd] is not a valid option for metrics manager.'],
        ];
    }

}
