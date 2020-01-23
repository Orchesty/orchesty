<?php declare(strict_types=1);

namespace Tests\Unit\Metrics\Manager;

use Exception;
use Hanaboso\PipesFramework\Metrics\Manager\MetricsManagerAbstract;
use Hanaboso\PipesFramework\Metrics\Manager\MetricsManagerLoader;
use Hanaboso\PipesPhpSdk\Database\Document\Topology;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\KernelTestCaseAbstract;

/**
 * Class MetricsManagerLoaderTest
 *
 * @package Tests\Unit\Metrics\Manager
 */
final class MetricsManagerLoaderTest extends KernelTestCaseAbstract
{

    /**
     * @covers       MetricsManagerLoader::getManager
     *
     * @dataProvider loaderDataProvider
     *
     * @param MetricsManagerLoader $loader
     * @param string               $service
     * @param string|null          $err
     *
     * @throws Exception
     */
    public function testGetManager(MetricsManagerLoader $loader, string $service, ?string $err): void
    {
        if ($err) {
            self::expectException(LogicException::class);
            self::expectExceptionMessage($err);
        }

        /** @var Topology|MockObject $topo */
        $topo = self::createMock(Topology::class);

        self::assertEquals($service, $loader->getManager()->getTopologyMetrics($topo, [])[0]);
    }

    /**
     * @return mixed[]
     */
    public function loaderDataProvider(): array
    {
        /** @var MetricsManagerAbstract|MockObject $influx */
        $influx = self::createMock(MetricsManagerAbstract::class);
        $influx->method('getTopologyMetrics')->willReturn(['influx']);
        /** @var MetricsManagerAbstract|MockObject $mongo */
        $mongo = self::createMock(MetricsManagerAbstract::class);
        $mongo->method('getTopologyMetrics')->willReturn(['mongo']);

        return [
            [new MetricsManagerLoader('mongo', $influx, $mongo), 'mongo', NULL],
            [new MetricsManagerLoader('influx', $influx, $mongo), 'influx', NULL],
            [new MetricsManagerLoader('asd', $influx, $mongo), '', '[asd] is not a valid option for metrics manager.'],
        ];
    }

}
