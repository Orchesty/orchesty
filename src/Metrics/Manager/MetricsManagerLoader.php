<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Manager;

use LogicException;

/**
 * Class MetricsManagerLoader
 *
 * @package Hanaboso\PipesFramework\Metrics\Manager
 */
final class MetricsManagerLoader
{

    /**
     * MetricsManagerLoader constructor.
     *
     * @param string                 $metricsService
     * @param MetricsManagerAbstract $influxManager
     * @param MetricsManagerAbstract $mongoManager
     */
    public function __construct(
        private string $metricsService,
        private MetricsManagerAbstract $influxManager,
        private MetricsManagerAbstract $mongoManager,
    )
    {
    }

    /**
     * @return MetricsManagerAbstract
     */
    public function getManager(): MetricsManagerAbstract
    {
        switch ($this->metricsService) {
            case 'mongo':
                return $this->mongoManager;
            case 'influx':
                return $this->influxManager;
            default:
                throw new LogicException(
                    sprintf('[%s] is not a valid option for metrics manager.', $this->metricsService),
                );
        }
    }

}
