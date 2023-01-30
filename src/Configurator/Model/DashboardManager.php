<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Model;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Configurator\Document\TopologyProgress;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Database\Document\Topology;
use Hanaboso\Utils\Exception\DateTimeException;

/**
 * Class DashboardManager
 *
 * @package Hanaboso\PipesFramework\Configurator\Model
 */
final class DashboardManager
{

    /**
     * DashboardManager constructor.
     *
     * @param DocumentManager $documentManager
     */
    public function __construct(private readonly DocumentManager $documentManager)
    {
    }

    /**
     * @param string $range
     *
     * @return DashboardDto
     * @throws DateTimeException
     */
    public function getDashboardData(string $range): DashboardDto
    {
        $data = new DashboardDto();
        $data->setRange($range);

        $repository        = $this->documentManager->getRepository(TopologyProgress::class);
        $topologiesMetrics = $repository->getDataForDashboard($this->rangeToWord($range));
        $data->setTotalRuns(intval($topologiesMetrics['total']));
        $data->setErrorsCount(intval($topologiesMetrics['failed']));
        $data->setSuccessCount(intval($topologiesMetrics['total']) - intval($topologiesMetrics['failed']));

        $repository = $this->documentManager->getRepository(Topology::class);

        $data->setActiveTopologies($repository->getCountByEnable(TRUE));
        $data->setDisabledTopologies($repository->getCountByEnable(FALSE));

        $appRepository = $this->documentManager->getRepository(ApplicationInstall::class);
        $installs      = $appRepository->getApplicationsCount();
        $installedApps = 0;
        foreach ($installs as $install) {
            $installedApps += $install['value']['total_sum'];
        }
        $data->setInstalledApps($installedApps);

        return $data;
    }

    /**
     * @param string $range
     *
     * @return mixed[]
     */
    private function rangeToWord(string $range): array
    {
        $matches = [];
        preg_match('/(\d+)([hmsd])/', $range, $matches);

        $toWord = [
            'd' => 'day',
            'h' => 'hour',
            'm' => 'minute',
            's' => 'second',
        ];

        return [
            'to'   => 'now',
            'from' => sprintf('-%s %s', $matches[1], $toWord[$matches[2]]),
        ];
    }

}
