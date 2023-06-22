<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFUsageStatsBundle\Manager;

use Hanaboso\PipesFramework\UsageStats\Repository\UsageStatsEventRepository;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Interface SenderInterface
 *
 * @package Hanaboso\PipesFramework\HbPFUsageStatsBundle\Manager
 */
interface SenderInterface
{

    /**
     * @param string                    $alphaInstanceId
     * @param UsageStatsEventRepository $usageStatsEventRepository
     * @param OutputInterface           $output
     *
     * @return int
     */
    public function send(
        string $alphaInstanceId,
        UsageStatsEventRepository $usageStatsEventRepository,
        OutputInterface $output,
    ): int;

}
