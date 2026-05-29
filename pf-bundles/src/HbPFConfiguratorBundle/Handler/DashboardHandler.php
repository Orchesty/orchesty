<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler;

use Hanaboso\PipesFramework\Configurator\Model\DashboardManager;
use Hanaboso\Utils\Exception\DateTimeException;

/**
 * Class DashboardHandler
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler
 */
final class DashboardHandler
{

    /**
     * DashboardHandler constructor.
     *
     * @param DashboardManager $metricsManager
     */
    public function __construct(private DashboardManager $metricsManager)
    {
    }

    /**
     * @param string $range
     *
     * @return mixed[]
     * @throws DateTimeException
     */
    public function getMetrics(string $range): array
    {
        return $this->metricsManager->getDashboardData($range)->toArray();
    }

}
