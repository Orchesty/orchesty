<?php declare(strict_types=1);

namespace Hanaboso\HbPFAppStore\Handler;

use Hanaboso\HbPFAppStore\Model\StatisticsManager;
use Hanaboso\Utils\Exception\DateTimeException;

/**
 * Class StatisticsHandler
 *
 * @package Hanaboso\HbPFAppStore\Handler
 */
final class StatisticsHandler
{

    /**
     * @var StatisticsManager
     */
    private StatisticsManager $statisticsManager;

    /**
     * StatisticsHandler constructor.
     *
     * @param StatisticsManager $statisticsManager
     */
    public function __construct(StatisticsManager $statisticsManager)
    {
        $this->statisticsManager = $statisticsManager;
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
     * @param string $application
     *
     * @return mixed[]
     * @throws DateTimeException
     */
    public function getApplicationsUsers(string $application): array
    {
        return $this->statisticsManager->getApplicationsUsers($application);
    }

}
