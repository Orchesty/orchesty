<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Model;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\MongoDataGrid\GridFilterAbstract;
use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\PipesFramework\Logs\LogsInterface;
use Hanaboso\PipesFramework\Metrics\Manager\MetricsManagerLoader;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Database\Document\Topology;
use Hanaboso\Utils\Date\DateTimeUtils;
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
     * @param LogsInterface        $logs
     * @param MetricsManagerLoader $metricsManager
     * @param DocumentManager      $documentManager
     */
    public function __construct(
        private LogsInterface $logs,
        private MetricsManagerLoader $metricsManager,
        private DocumentManager $documentManager,
    )
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
        $date = $this->rangeToTimestamp($range);

        $errorLogs = $this->getErrorLogs($date);
        if ($errorLogs['items']) {
            $data->setErrorLogs($errorLogs['items']);
        }

        // TODO
        //        $alertLogs = $this->getAlertLogs($date);
        //        if ($alertLogs['items']) {
        //            $data->setAlertLogs($alertLogs['items']);
        //        }

        $topologiesMetrics = $this->metricsManager->getManager()->getTopologiesProcessTimeMetrics(
            $this->rangeToWord($range),
        );
        if (array_key_exists('process', $topologiesMetrics)) {
            $process = $topologiesMetrics['process'];
            $data->setTotalRuns(intval($process['total']));
            $data->setErrorsCount(intval($process['errors']));
            $data->setSuccessCount(intval($process['total']) - intval($process['errors']));
        }

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
     * @param mixed[] $date
     * @param int     $items
     *
     * @return mixed[]
     */
    protected function getErrorLogs(array $date, int $items = 10): array
    {
        $request = new GridRequestDto(
            [
                'filter' => [
                    [
                        [
                            GridFilterAbstract::COLUMN   => 'timestamp',
                            GridFilterAbstract::OPERATOR => GridFilterAbstract::GTE,
                            GridFilterAbstract::VALUE    => $date['from'],
                        ],
                    ],
                    [
                        [
                            GridFilterAbstract::COLUMN   => 'timestamp',
                            GridFilterAbstract::OPERATOR => GridFilterAbstract::LTE,
                            GridFilterAbstract::VALUE    => $date['to'],
                        ],
                    ],
                    [
                        [
                            GridFilterAbstract::COLUMN   => 'topology_id',
                            GridFilterAbstract::OPERATOR => GridFilterAbstract::NEMPTY,
                        ],
                    ],
                    [
                        [
                            GridFilterAbstract::COLUMN   => 'topology_id',
                            GridFilterAbstract::OPERATOR => GridFilterAbstract::EXIST,
                        ],
                    ],
                ],
            ],
        );

        $request->setItemsPerPage($items);

        return $this->logs->getData($request, 0);
    }

    /**
     * @param mixed[] $date
     * @param int     $items
     *
     * @return mixed[]
     */
    protected function getAlertLogs(array $date, int $items = 10): array
    {
        $request = new GridRequestDto(
            [
                'filter' => [
                    [
                        [
                            GridFilterAbstract::COLUMN   => 'timestamp',
                            GridFilterAbstract::OPERATOR => GridFilterAbstract::GTE,
                            GridFilterAbstract::VALUE    => $date['from'],
                        ],
                        [
                            GridFilterAbstract::COLUMN   => 'timestamp',
                            GridFilterAbstract::OPERATOR => GridFilterAbstract::LTE,
                            GridFilterAbstract::VALUE    => $date['to'],
                        ],
                        [
                            GridFilterAbstract::COLUMN   => 'severity',
                            GridFilterAbstract::OPERATOR => GridFilterAbstract::EQ,
                            GridFilterAbstract::VALUE    => 'INFO',
                        ],
                        [
                            GridFilterAbstract::COLUMN   => 'severity',
                            GridFilterAbstract::OPERATOR => GridFilterAbstract::EQ,
                            GridFilterAbstract::VALUE    => 'WARNING',
                        ],
                    ],
                ],
            ],
        );

        $request->setItemsPerPage($items);

        return $this->logs->getData($request, 0);
    }

    /**
     * @param string $range
     *
     * @return mixed[]
     * @throws DateTimeException
     */
    private function rangeToTimestamp(string $range): array
    {
        $matches = [];
        preg_match('/(\d+)([hms])/', $range, $matches);

        $toWord = [
            'h' => 'hours',
            'm' => 'minutes',
            's' => 'seconds',
        ];

        return [
            'to'   => DateTimeUtils::getUtcDateTime()->format('c'),
            'from' => DateTimeUtils::getUtcDateTime(sprintf('-%s %s', $matches[1], $toWord[$matches[2]]))->format('c'),
        ];
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
