<?php declare(strict_types=1);

namespace Hanaboso\HbPFAppStore\Handler;

use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\HbPFAppStore\Model\StatisticsManager;

/**
 * Class StatisticsHandler
 *
 * @package Hanaboso\HbPFAppStore\Handler
 */
class StatisticsHandler
{

    /**
     * @var StatisticsManager
     */
    private $statisticsManager;

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
     * @return array
     * @throws MongoDBException
     */
    public function getApplicationsBasicData(): array
    {
        return $this->statisticsManager->getApplicationsBasicData();
    }

    /**
     * @param string $application
     *
     * @return array
     * @throws MongoDBException
     */
    public function getApplicationsUsers(string $application): array
    {
        return $this->statisticsManager->getApplicationsUsers($application);
    }

}
