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
     * @var string
     */
    private string $metricsService;

    /**
     * @var MetricsManagerAbstract
     */
    private MetricsManagerAbstract $influxManager;

    /**
     * @var MetricsManagerAbstract
     */
    private MetricsManagerAbstract $mongoManager;

    /**
     * MetricsManagerLoader constructor.
     *
     * @param string                 $metricsService
     * @param MetricsManagerAbstract $influxManager
     * @param MetricsManagerAbstract $mongoManager
     */
    public function __construct(
        string $metricsService,
        MetricsManagerAbstract $influxManager,
        MetricsManagerAbstract $mongoManager
    )
    {
        $this->metricsService = $metricsService;
        $this->influxManager  = $influxManager;
        $this->mongoManager   = $mongoManager;
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
                    sprintf('[%s] is not a valid option for metrics manager.', $this->metricsService)
                );
        }
    }

}
