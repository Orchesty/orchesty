<?php declare(strict_types=1);

namespace Hanaboso\Applinth\Handler;

use Hanaboso\Applinth\Manager\StatisticsManager;
use Hanaboso\PipesFramework\Metrics\Manager\MetricsManagerLoader;
use Hanaboso\Utils\Exception\DateTimeException;

/**
 * Class StatisticsHandler
 *
 * @package Hanaboso\Applinth\Handler
 */
final class StatisticsHandler
{

    /**
     * StatisticsHandler constructor.
     *
     * @param StatisticsManager    $statisticsManager
     * @param MetricsManagerLoader $loader
     */
    public function __construct(private StatisticsManager $statisticsManager, private MetricsManagerLoader $loader)
    {
    }

    /**
     * @return mixed[]
     * @throws DateTimeException
     */
    public function getApplicationsBasicData(): array
    {
        return $this->statisticsManager->getApplicationsBasicData();
    }

    /**
     * @return mixed[]
     * @throws DateTimeException
     */
    public function getUsersBasicData(): array
    {
        return $this->statisticsManager->getUsersBasicData();
    }

    /**
     * @param string $application
     *
     * @return mixed[]
     * @throws DateTimeException
     */
    public function getApplicationsUsers(string $application): array
    {
        return $this->statisticsManager->getApplicationsUsers($application);
    }

    /**
     * @param mixed[]     $params
     * @param string|null $key
     *
     * @return mixed[]
     */
    public function getApplicationMetrics(array $params, ?string $key): array
    {
        return $this->loader->getManager()->getApplicationMetrics($params, $key);
    }

    /**
     * @param mixed[]     $params
     * @param string|null $user
     *
     * @return mixed[]
     */
    public function getUserMetrics(array $params, ?string $user): array
    {
        return $this->loader->getManager()->getUserMetrics($params, $user);
    }

}
